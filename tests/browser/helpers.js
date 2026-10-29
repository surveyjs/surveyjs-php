// Shared by the browser tests: rows of the requests log, the 1.1 form and a streaming server.
import http from 'node:http';
import { expect } from '@playwright/test';

/** The latest row of the requests log for a method and a URL that starts with path. */
export function row(page, method, path) {
    return page
        .locator('#requests > li')
        .filter({ has: page.locator('[data-field="method"]', { hasText: new RegExp(`^${method}$`) }) })
        .filter({ has: page.locator('[data-field="url"]', { hasText: new RegExp(`^${path.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`) }) })
        .first();
}

/** The text of a row's body slot, once it is no longer the empty template. */
export async function slot(rowLocator, name) {
    const element = rowLocator.locator(`[data-field="${name}"]`);
    await expect.poll(() => element.textContent(), { timeout: 10_000 }).not.toBe('');
    return element.textContent();
}

/** 1.1: fill in the form and press Complete. */
export async function completeSaveResponse(page, email = 'ann@example.com') {
    await page.getByRole('textbox', { name: /Your email/ }).fill(email);
    await page.getByRole('button', { name: 'Complete' }).click();
}

/**
 * A local server that streams 1 MB in 16 KB chunks, without Content-Length (Playwright's own
 * route.fulfill() always sets one). /…pdf answers application/pdf, anything else text/plain.
 * route.continue() sends a page request here: the page still sees it as same-origin.
 */
export async function startStreamServer() {
    const server = http.createServer((req, res) => {
        res.writeHead(200, { 'content-type': req.url.includes('pdf') ? 'application/pdf' : 'text/plain; charset=utf-8' });
        let sent = 0;
        const timer = setInterval(() => {
            if (sent >= 1 << 20 || res.destroyed) {
                clearInterval(timer);
                res.end();
                return;
            }
            res.write('a'.repeat(16384));
            sent += 16384;
        }, 5);
    });
    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    return { url: `http://127.0.0.1:${server.address().port}`, close: () => new Promise((resolve) => server.close(resolve)) };
}

/** Init script: record the URL of every Response.prototype.clone() call in window.__clones. */
export function spyOnClone() {
    window.__clones = [];
    const clone = Response.prototype.clone;
    Response.prototype.clone = function (...args) {
        window.__clones.push(new URL(this.url).pathname);
        return clone.apply(this, args);
    };
}

/** Init script: record every reader from ReadableStream.prototype.getReader in window.__readers. */
export function spyOnReaders() {
    window.__readers = [];
    const getReader = ReadableStream.prototype.getReader;
    ReadableStream.prototype.getReader = function (...args) {
        const reader = getReader.apply(this, args);
        const stats = { reads: 0, bytes: 0, bytesBeforeLastRead: 0, cancelled: false };
        window.__readers.push(stats);
        const read = reader.read.bind(reader);
        const cancel = reader.cancel.bind(reader);
        reader.read = async () => {
            stats.reads++;
            stats.bytesBeforeLastRead = stats.bytes;
            const chunk = await read();
            if (!chunk.done) stats.bytes += chunk.value.byteLength;
            return chunk;
        };
        reader.cancel = (reason) => {
            stats.cancelled = true;
            return cancel(reason);
        };
        return reader;
    };
}
