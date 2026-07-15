import { Link } from '@inertiajs/react';

export interface PaginationLink { url: string | null; label: string; active: boolean }

export function Pagination({ links }: { links: PaginationLink[] }) {
    return <div className="flex flex-wrap gap-1">{links.map((link, index) => link.url ? <Link className={`rounded-md border px-3 py-1.5 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`} href={link.url} key={`${link.label}-${index}`} dangerouslySetInnerHTML={{ __html: link.label }} /> : <span className="rounded-md border px-3 py-1.5 text-sm text-muted-foreground" key={`${link.label}-${index}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}</div>;
}
