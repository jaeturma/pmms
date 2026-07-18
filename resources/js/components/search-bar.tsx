import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    initial: string;
    placeholder: string;
    url: string;
    extraParams?: Record<string, string>;
};

export function SearchBar({
    initial,
    placeholder,
    url,
    extraParams = {},
}: Props) {
    const [value, setValue] = useState(initial);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            url,
            { ...extraParams, ...(value ? { search: value } : {}) },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <form onSubmit={submit} className="flex max-w-sm gap-2">
            <Input
                value={value}
                onChange={(e) => setValue(e.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
            />
            <Button type="submit" variant="outline">
                <Search />
                Search
            </Button>
        </form>
    );
}
