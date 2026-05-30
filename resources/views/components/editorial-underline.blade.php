<span {{ $attributes->class([
    'relative inline',
    "after:absolute after:-bottom-0.5 after:left-0 after:h-[1.5px] after:w-0 after:bg-current after:content-['']",
    'after:transition-[width] after:duration-[400ms] after:ease-[cubic-bezier(0.25,0.46,0.45,0.94)]',
    'hover:after:w-full group-hover:after:w-full',
]) }}>{{ $slot }}</span>
