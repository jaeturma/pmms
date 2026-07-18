import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M12 1.5 21 5v6.3c0 5.4-3.8 9.2-9 11.2-5.2-2-9-5.8-9-11.2V5l9-3.5Zm0 4.1 2 4.05 4.47.65-3.23 3.15.76 4.45L12 15.8l-4 2.1.76-4.45L5.53 10.3 10 9.65l2-4.05Z"
            />
        </svg>
    );
}
