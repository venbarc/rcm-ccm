import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="7" y="4.5" width="18" height="23" rx="4" stroke="currentColor" strokeWidth="2.2" />
            <path d="M11.5 11H20.5M11.5 16H20.5M11.5 21H16.5" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" />
            <path d="M18.5 22.5L20.5 24.5L25 19" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
