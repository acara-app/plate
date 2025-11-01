import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            width="100%"
            height="100%"
            viewBox="0 0 2392 2392"
            version="1.1"
            xmlns="http://www.w3.org/2000/svg"
            xmlnsXlink="http://www.w3.org/1999/xlink"
            xmlSpace="preserve"
            style={{
                fillRule: 'evenodd',
                clipRule: 'evenodd',
                strokeLinejoin: 'round',
                strokeMiterlimit: 2,
            }}
            {...props}
        >
            <defs>
                <linearGradient
                    id="emeraldGradient1"
                    x1="0%"
                    y1="0%"
                    x2="100%"
                    y2="100%"
                >
                    <stop
                        offset="0%"
                        style={{ stopColor: '#6ee7b7', stopOpacity: 1 }}
                    />
                    <stop
                        offset="50%"
                        style={{ stopColor: '#10b981', stopOpacity: 1 }}
                    />
                    <stop
                        offset="100%"
                        style={{ stopColor: '#047857', stopOpacity: 1 }}
                    />
                </linearGradient>
                <linearGradient
                    id="emeraldGradient2"
                    x1="100%"
                    y1="100%"
                    x2="0%"
                    y2="0%"
                >
                    <stop
                        offset="0%"
                        style={{ stopColor: '#34d399', stopOpacity: 1 }}
                    />
                    <stop
                        offset="50%"
                        style={{ stopColor: '#059669', stopOpacity: 1 }}
                    />
                    <stop
                        offset="100%"
                        style={{ stopColor: '#064e3b', stopOpacity: 1 }}
                    />
                </linearGradient>
                <filter id="glow">
                    <feGaussianBlur stdDeviation="8" result="coloredBlur" />
                    <feMerge>
                        <feMergeNode in="coloredBlur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
                <filter id="shine">
                    <feGaussianBlur in="SourceAlpha" stdDeviation="4" />
                    <feOffset dx="2" dy="2" result="offsetblur" />
                    <feComponentTransfer>
                        <feFuncA type="linear" slope="0.3" />
                    </feComponentTransfer>
                    <feMerge>
                        <feMergeNode />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
            </defs>
            <g filter="url(#glow)">
                <path
                    d="M1716.4,674.8L1716.4,0L0,0L0,1716.4L674.8,1716.4L674.8,674.8L1716.4,674.8Z"
                    style={{
                        fill: 'url(#emeraldGradient1)',
                        fillRule: 'nonzero',
                    }}
                    filter="url(#shine)"
                />
                <path
                    d="M1716.4,1716.4L674.8,1716.4L674.8,2391.2L2391.2,2391.2L2391.2,674.8L1716.4,674.8L1716.4,1716.4Z"
                    style={{
                        fill: 'url(#emeraldGradient2)',
                        fillRule: 'nonzero',
                    }}
                    filter="url(#shine)"
                />
                {/* Highlight facets for crystalline effect */}
                <path
                    d="M1716.4,674.8L1716.4,0L0,0L0,1716.4L674.8,1716.4L674.8,674.8L1716.4,674.8Z"
                    style={{
                        fill: 'url(#highlight1)',
                        fillRule: 'nonzero',
                        opacity: 0.4,
                    }}
                />
                <path
                    d="M1716.4,1716.4L674.8,1716.4L674.8,2391.2L2391.2,2391.2L2391.2,674.8L1716.4,674.8L1716.4,1716.4Z"
                    style={{
                        fill: 'url(#highlight2)',
                        fillRule: 'nonzero',
                        opacity: 0.3,
                    }}
                />
            </g>
            <defs>
                <linearGradient
                    id="highlight1"
                    x1="0%"
                    y1="0%"
                    x2="50%"
                    y2="50%"
                >
                    <stop
                        offset="0%"
                        style={{ stopColor: '#ffffff', stopOpacity: 1 }}
                    />
                    <stop
                        offset="100%"
                        style={{ stopColor: '#ffffff', stopOpacity: 0 }}
                    />
                </linearGradient>
                <linearGradient
                    id="highlight2"
                    x1="100%"
                    y1="100%"
                    x2="50%"
                    y2="50%"
                >
                    <stop
                        offset="0%"
                        style={{ stopColor: '#000000', stopOpacity: 0.3 }}
                    />
                    <stop
                        offset="100%"
                        style={{ stopColor: '#ffffff', stopOpacity: 0 }}
                    />
                </linearGradient>
            </defs>
        </svg>
    );
}
