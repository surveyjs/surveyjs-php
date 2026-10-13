@extends('layouts.example')

@section('try')
    <li>Type <code>taken@example.com</code> as the email and leave the field: after <code>GET /api/customers/exists</code> returns, the form shows "This email is already registered".</li>
    <li>Type any other email: the same request returns <code>{"exists": false}</code> and the error goes away.</li>
    <li>Type a postcode (<code>10115</code>, <code>10999</code>, <code>80331</code> or <code>SW1A 1AA</code>): Shipping updates when <code>GET /api/shipping</code> returns.</li>
    <li>Type a postcode the server has no rate for, such as <code>99999</code>: the same request returns <code>{"price": null}</code>, and the form shows "We don't deliver to this postcode yet".</li>
@endsection
