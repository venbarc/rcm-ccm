import AppLogoIcon from '@/components/app-logo-icon';
import { Head, Link } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <main className="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top_left,_rgb(224_242_254),_transparent_42%),linear-gradient(135deg,_#ffffff,_#f4f9fc)] px-6">
                <section className="w-full max-w-lg rounded-3xl border border-sky-100 bg-white/90 p-10 text-center shadow-xl shadow-sky-950/10 backdrop-blur">
                    <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-[#1b516f] text-white">
                        <AppLogoIcon className="size-9" />
                    </div>
                    <h1 className="mt-6 text-3xl font-extrabold tracking-tight text-slate-900">RCM CCM</h1>
                    <p className="mt-2 text-sm text-slate-600">Multi-account claims management</p>
                    <Link
                        href="/dashboard"
                        className="mt-8 inline-flex h-11 items-center justify-center rounded-xl bg-[#1b516f] px-6 text-sm font-semibold text-white transition hover:bg-[#143d54]"
                    >
                        Open dashboard
                    </Link>
                </section>
            </main>
        </>
    );
}
