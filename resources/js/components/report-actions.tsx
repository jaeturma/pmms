import { Download, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Props = {
    downloadUrl: string;
};

export function ReportActions({ downloadUrl }: Props) {
    return (
        <div className="flex gap-2 print:hidden">
            <Button variant="outline" asChild>
                <a href={downloadUrl}>
                    <Download aria-hidden="true" />
                    Download CSV
                </a>
            </Button>
            <Button onClick={() => window.print()}>
                <Printer aria-hidden="true" />
                Print
            </Button>
        </div>
    );
}
