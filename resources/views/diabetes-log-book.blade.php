@section('title', 'Free Printable Diabetes Log Book PDF | Track Blood Sugar Daily')
@section('meta_description', 'Download our free printable diabetes log book (6"x9" PDF). Easily track daily blood sugar readings, meals, and insulin. Print your copy today!')

<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        {{-- Screen-only controls --}}
        <div class="print:hidden">
            <a
                href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
                class="-mt-10 mb-12 flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
                wire:navigate
            >
                <x-icons.chevron-left class="size-4" />
                <span>Back</span>
            </a>

            <div class="mx-auto max-w-4xl">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-4">Diabetes Log Book</h1>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    A simple printable log book for tracking your daily blood sugar readings. Perfect 6" × 9" size to carry with you.
                </p>

                <div class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-900 dark:text-blue-100 mb-2">
                        <strong>Desktop Only:</strong> This feature is optimized for desktop browsers.
                    </p>
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>To Download:</strong> Click the "Print / Download PDF" button below, then in the print dialog, select "Save as PDF" as your destination.
                    </p>
                </div>

                <button
                    onclick="window.print()"
                    class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-medium mb-8"
                >
                    <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print / Download PDF
                </button>

                <div class="bg-slate-100 dark:bg-slate-800 p-8 rounded-lg">
                    <p class="text-sm text-slate-600 dark:text-slate-400 text-center mb-4">Preview (not to scale)</p>
                    
                    {{-- Preview of printable content --}}
                    <div class="bg-white dark:bg-slate-900 p-6 rounded shadow-sm border border-slate-200 dark:border-slate-700">
                        <div class="space-y-4">
                            <div class="text-center pb-2 border-b-2 border-slate-900 dark:border-slate-100">
                                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Diabetes Log Book</h2>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">Weight:</span>
                                    <span class="border-b border-slate-400 flex-1">_________________</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">Week Starting:</span>
                                    <span class="border-b border-slate-400 flex-1">_________________</span>
                                </div>
                            </div>

                            <table class="w-full border-collapse border-2 border-slate-900 dark:border-slate-100 text-xs">
                                <thead>
                                    <tr>
                                        <th class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-2 font-bold text-slate-900 dark:text-slate-100">Day</th>
                                        <th class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-2 font-bold text-slate-900 dark:text-slate-100">Meal</th>
                                        <th colspan="2" class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-2 font-bold text-slate-900 dark:text-slate-100">Blood Sugar</th>
                                        <th class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-2 font-bold text-slate-900 dark:text-slate-100">Notes</th>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-1"></th>
                                        <th class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-1"></th>
                                        <th class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-1 font-bold text-slate-900 dark:text-slate-100">Before</th>
                                        <th class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-1 font-bold text-slate-900 dark:text-slate-100">After</th>
                                        <th class="border border-slate-900 dark:border-slate-100 bg-slate-200 dark:bg-slate-700 p-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $day)
                                        @foreach(['Breakfast', 'Lunch', 'Dinner', 'Bedtime'] as $index => $meal)
                                            <tr>
                                                @if($index === 0)
                                                    <td rowspan="4" class="border border-slate-900 dark:border-slate-100 p-2 text-center font-bold align-top text-slate-900 dark:text-slate-100">
                                                        {{ $day }}<br><span class="text-[10px] font-normal text-slate-600 dark:text-slate-400">...../...../....</span>
                                                    </td>
                                                @endif
                                                <td class="border border-slate-900 dark:border-slate-100 p-2 font-semibold text-slate-900 dark:text-slate-100">{{ $meal }}</td>
                                                <td class="border border-slate-900 dark:border-slate-100 p-2"></td>
                                                <td class="border border-slate-900 dark:border-slate-100 p-2"></td>
                                                @if($index === 0)
                                                    <td rowspan="4" class="border border-slate-900 dark:border-slate-100 p-2 align-top"></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Print-only content --}}
        <style>
            @media print {
                @page {
                    size: 6in 9in;
                    margin: 0.3in 0.4in;
                }
                body {
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .print-logbook {
                    font-size: 9pt;
                }
                .print-logbook h2 {
                    font-size: 14pt;
                }
                .print-logbook .field-section {
                    font-size: 8pt;
                }
                .print-logbook table {
                    font-size: 7pt;
                    line-height: 1.2;
                }
                .print-logbook .date-placeholder {
                    font-size: 6pt;
                }
            }
        </style>
        <div class="hidden print:block print:m-0 print:p-0 print-logbook">
            <div class="text-center print:pb-2 border-b-2 border-black print:mb-2">
                <h2 class="text-xl font-bold text-black print:text-[14pt]">Diabetes Log Book</h2>
            </div>
            
            <div class="grid grid-cols-2 gap-2 text-sm print:gap-1 print:my-2 field-section">
                <div class="flex items-center gap-1">
                    <span class="font-semibold text-black">Weight:</span>
                    <span class="border-b border-black flex-1">__________________</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="font-semibold text-black">Week Starting:</span>
                    <span class="border-b border-black flex-1">__________________</span>
                </div>
            </div>

            <table class="w-full border-collapse border-2 border-black text-xs print:mt-1 print:text-[7pt]">
                <thead>
                    <tr>
                        <th class="border border-black bg-slate-200 print:bg-slate-200 p-1 print:p-1 font-bold text-black">Day</th>
                        <th class="border border-black bg-slate-200 print:bg-slate-200 p-1 print:p-1 font-bold text-black">Meal</th>
                        <th colspan="2" class="border border-black bg-slate-200 print:bg-slate-200 p-1 print:p-1 font-bold text-black">Blood Sugar</th>
                        <th class="border border-black bg-slate-200 print:bg-slate-200 p-1 print:p-1 font-bold text-black">Notes</th>
                    </tr>
                    <tr>
                        <th class="border border-black bg-slate-200 print:bg-slate-200 p-0.5 print:p-1"></th>
                        <th class="border border-black bg-slate-200 print:bg-slate-200 p-0.5 print:p-1"></th>
                        <th class="border border-black bg-slate-200 print:bg-slate-200 p-0.5 print:p-1 font-bold text-black">Before</th>
                        <th class="border border-black bg-slate-200 print:bg-slate-200 p-0.5 print:p-1 font-bold text-black">After</th>
                        <th class="border border-black bg-slate-200 print:bg-slate-200 p-0.5 print:p-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $day)
                        @foreach(['Breakfast', 'Lunch', 'Dinner', 'Bedtime'] as $index => $meal)
                            <tr>
                                @if($index === 0)
                                    <td rowspan="4" class="border border-black p-1 print:p-[2pt] text-center font-bold align-top text-black">
                                        {{ $day }}<br><span class="text-xs print:text-[6pt] font-normal text-slate-600 print:text-slate-600 date-placeholder">...../...../....</span>
                                    </td>
                                @endif
                                <td class="border border-black p-1 print:p-[2pt] font-semibold text-black">{{ $meal }}</td>
                                <td class="border border-black p-1 print:p-[2pt] print:min-h-4"></td>
                                <td class="border border-black p-1 print:p-[2pt] print:min-h-4"></td>
                                @if($index === 0)
                                    <td rowspan="4" class="border border-black p-1 print:p-[2pt] align-top"></td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-default-layout>
