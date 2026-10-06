{{-- Demo only: how to turn on a Section IV switch, and where the service runs --}}
@if (isset($switch) && ! $on)
    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        <strong>{{ $switch }} is off</strong>, so this page runs the plain route and nothing is checked. Set <code>{{ $switch }}=true</code> in <code>.env</code>
        (<code>routes/examples.php</code> then loads this step's route file instead of <code>{{ $replaces }}</code>), start the service with
        <code>docker compose up -d surveyjs</code>, and reload.
    </p>
@else
    <p class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
        The SurveyJS service is called at <code>{{ config('surveyjs.service_url') }}</code>. If it isn't running, the endpoint answers
        <code>503</code> "SurveyJS service is not running": start it with <code>docker compose up -d surveyjs</code>.
    </p>
@endif
