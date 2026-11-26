<script>
    window.addEventListener('load', function() {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const sessionTimezone = '{{ session()->get('timezone', 'UTC') }}';
        const accountTimezone = '{{ auth()->user()?->resolveTimezone() ?? session()->get('timezone', 'UTC') }}';

        if (timezone !== sessionTimezone || timezone !== accountTimezone) {
            fetch('{{ route('profile.timezone.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ timezone: timezone })
            });
        }
    });
</script>
