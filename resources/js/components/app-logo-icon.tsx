import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" {...props}>
            <text
                x="50%"
                y="55%"
                dominantBaseline="middle"
                textAnchor="middle"
                fontSize="32"
            >
                🍓
            </text>
        </svg>
    );
}
