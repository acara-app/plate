@section('title', 'How Accurate Is Our AI Food Photo Analysis? | Acara Plate')
@section('meta_description', 'An honest breakdown of how Acara Plate\'s AI food photo analysis works, how accurate it realistically is, its known limitations, what the confidence score means, and what happens to your photos.')

@use(App\Services\AiTransparency)

<x-default-layout>
    <x-json-ld.ai-accuracy />
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
            class="-mt-10 mb-12 flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
            wire:navigate
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back</span>
        </a>
        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl">
                <h1>AI Accuracy &amp; Limitations</h1>

                <p>
                    "How accurate is it?" is the right first question to ask any AI nutrition tool. This page is our honest answer — what the <a href="{{ route('snap-to-track') }}">food photo analyzer</a> actually does, where it predictably fails, and what its numbers do and don't mean.
                </p>

                <h2>How the photo analysis works</h2>
                @foreach (AiTransparency::pipeline() as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach

                @if (AiTransparency::usesReferenceLookup())
                    <h2>Where each number comes from</h2>
                    @foreach (AiTransparency::provenance() as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                @endif

                <h2>How accurate is it, honestly?</h2>
                @foreach (AiTransparency::accuracy() as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
                <p>The research we lean on:</p>
                <ul>
                    @foreach (AiTransparency::literature() as $reference)
                        <li><a href="{{ $reference['url'] }}" target="_blank" rel="noopener noreferrer">{{ $reference['label'] }}</a></li>
                    @endforeach
                </ul>

                <h2>Known limitations</h2>
                @foreach (AiTransparency::limitations() as $limitation)
                    <h3>{{ $limitation['title'] }}</h3>
                    <p>{{ $limitation['detail'] }}</p>
                @endforeach

                <h2>What the confidence score means — and what it doesn't</h2>
                @foreach (AiTransparency::confidence() as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach

                <h2>What this is for (and what it isn't)</h2>
                @foreach (AiTransparency::intendedUse() as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach

                <h2>What happens to your photo</h2>
                @foreach (AiTransparency::photoHandling() as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
                <p>
                    The full details live in our <a href="{{ route('privacy') }}">Privacy Policy</a>.
                </p>

                <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-800 rounded-lg">
                    <h3>Our standing rule</h3>
                    <p>
                        We don't publish a quantitative accuracy claim anywhere on this site unless a citable benchmark backs it. When our internal validation benchmark is complete, its measured results — including how real error relates to the confidence score — will be published on this page.
                    </p>
                    <p>
                        Questions this page doesn't answer are welcome at <a href="mailto:team@acara.app">team@acara.app</a>.
                    </p>
                </div>

                <p class="text-sm">
                    Last reviewed: {{ \Illuminate\Support\Carbon::parse(AiTransparency::LAST_REVIEWED)->format('F j, Y') }}
                </p>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
