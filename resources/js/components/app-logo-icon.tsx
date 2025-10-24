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
            <g>
                <path
                    d="M1716.4,674.8L1716.4,0L0,0L0,1716.4L674.8,1716.4L674.8,674.8L1716.4,674.8Z"
                    style={{
                        fill: 'currentColor',
                        fillRule: 'nonzero',
                    }}
                />
                <path
                    d="M1716.4,1716.4L674.8,1716.4L674.8,2391.2L2391.2,2391.2L2391.2,674.8L1716.4,674.8L1716.4,1716.4Z"
                    style={{
                        fill: 'currentColor',
                        fillRule: 'nonzero',
                    }}
                />
            </g>
        </svg>
    );
}
