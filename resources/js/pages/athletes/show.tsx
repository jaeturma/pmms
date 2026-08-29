import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Eye,
    Maximize2,
    Minus,
    Plus,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index } from '@/routes/athletes';

type Athlete = {
    id: number;
    first_name: string;
    last_name: string;
    sex_label: string;
    birthdate: string;
    age: number;
    lrn: string;
    grade_level: number;
    school: string;
    meet: string;
    delegation: string;
    coach: string | null;
    photo_url: string | null;
    sports_photo_url: string | null;
    sports: string;
    events: string;
    accreditation_status: string;
    can_update: boolean;
    documents: Array<{
        id: number;
        document: string;
        file_name: string;
        view_url: string;
        status: string;
        status_label: string;
    }>;
    achievements: Array<{
        medal: string;
        sport: string;
        event: string;
        team: boolean;
    }>;
};

const documentStatusClass: Record<string, string> = {
    submitted:
        'border-orange-200 bg-orange-100 text-orange-800 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-200',
    missing:
        'border-orange-200 bg-orange-100 text-orange-800 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-200',
    under_review:
        'border-sky-200 bg-sky-100 text-sky-800 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-200',
    verified:
        'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    rejected:
        'border-red-200 bg-red-100 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200',
};

type Props = {
    athlete: Athlete;
};

type AthleteDocument = Athlete['documents'][number];

export default function AthleteShow({ athlete }: Props) {
    const [selectedDocument, setSelectedDocument] =
        useState<AthleteDocument | null>(null);
    const [documentZoom, setDocumentZoom] = useState(1);
    const fullName = `${athlete.first_name} ${athlete.last_name}`;

    const fields: Array<[string, string]> = [
        ['Sex', athlete.sex_label],
        ['Birthdate', `${athlete.birthdate} (age ${athlete.age})`],
        ['LRN', athlete.lrn],
        ['Grade level', `Grade ${athlete.grade_level}`],
        ['Sports', athlete.sports || 'Not assigned'],
        ['Events', athlete.events || 'Not assigned'],
        ['Coach', athlete.coach || 'Not assigned'],
        ['Delegation', athlete.delegation],
        ['Eligibility Status', athlete.accreditation_status],
        ['School', athlete.school],
    ];

    return (
        <>
            <Head title={fullName} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={fullName}
                    description="Athlete profile. Views of this page are audited."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={index().url}>
                                <ArrowLeft />
                                Back to athletes
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-6 sm:grid-cols-[11rem_1fr]">
                                <div>
                                    <div className="flex aspect-[3/4] items-center justify-center overflow-hidden rounded-lg border bg-muted">
                                        {athlete.photo_url ? (
                                            <img
                                                src={athlete.photo_url}
                                                alt={`Passport photo of ${fullName}`}
                                                className="size-full object-cover"
                                                loading="lazy"
                                            />
                                        ) : (
                                            <p className="px-3 text-center text-sm text-muted-foreground">
                                                No passport photo
                                            </p>
                                        )}
                                    </div>
                                    <p className="mt-2 text-center text-xs text-muted-foreground">
                                        Passport photo
                                    </p>
                                </div>
                                <dl className="grid content-start gap-3 sm:grid-cols-2">
                                    {fields.map(([label, value]) => (
                                        <div key={label}>
                                            <dt className="text-sm text-muted-foreground">
                                                {label}
                                            </dt>
                                            <dd className="text-sm font-medium">
                                                {value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </div>

                            <div className="mt-6 overflow-hidden rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Document</TableHead>
                                            <TableHead>View</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {athlete.documents.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={3}
                                                    className="text-center text-muted-foreground"
                                                >
                                                    No documents uploaded.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            athlete.documents.map(
                                                (document) => (
                                                    <TableRow key={document.id}>
                                                        <TableCell className="font-medium">
                                                            {document.document}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Button
                                                                variant="outline"
                                                                onClick={() => {
                                                                    setDocumentZoom(
                                                                        1,
                                                                    );
                                                                    setSelectedDocument(
                                                                        document,
                                                                    );
                                                                }}
                                                            >
                                                                <Eye className="size-4" />
                                                                View
                                                            </Button>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant="outline"
                                                                className={
                                                                    documentStatusClass[
                                                                        document
                                                                            .status
                                                                    ]
                                                                }
                                                            >
                                                                {
                                                                    document.status_label
                                                                }
                                                            </Badge>
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                            <div className="mt-6">
                                <h3 className="mb-3 font-semibold">
                                    Medals / Achievements
                                </h3>
                                {athlete.achievements.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No official medal achievements yet.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {athlete.achievements.map(
                                            (achievement, index) => (
                                                <div
                                                    key={`${achievement.sport}-${achievement.event}-${index}`}
                                                    className="flex items-center justify-between rounded-md border px-3 py-2"
                                                >
                                                    <div>
                                                        <p className="font-medium">
                                                            {achievement.sport}{' '}
                                                            — {achievement.event}
                                                        </p>
                                                        {achievement.team && (
                                                            <p className="text-xs text-muted-foreground">
                                                                Team competition
                                                            </p>
                                                        )}
                                                    </div>
                                                    <Badge variant="outline">
                                                        {achievement.medal}
                                                    </Badge>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <div>
                        <Card>
                            <CardHeader>
                                <CardTitle>Sports photo</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {athlete.sports_photo_url ? (
                                    <img
                                        src={athlete.sports_photo_url}
                                        alt={`Sports photo of ${fullName}`}
                                        className="max-h-64 w-full rounded-lg object-contain"
                                        loading="lazy"
                                    />
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No sports photo on file.
                                    </p>
                                )}
                                <p className="mt-3 flex items-center gap-2 text-sm">
                                    {athlete.sports_photo_url ? (
                                        <>
                                            <CheckCircle2 className="size-4 text-emerald-600" />
                                            Uploaded
                                        </>
                                    ) : (
                                        <>
                                            <TriangleAlert className="size-4 text-amber-600" />
                                            Missing
                                        </>
                                    )}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Dialog
                    open={selectedDocument !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setSelectedDocument(null);
                            setDocumentZoom(1);
                        }
                    }}
                >
                    <DialogContent className="flex h-[95vh] w-[80vw] max-w-none flex-col gap-3 p-4 sm:max-w-none">
                        <DialogHeader className="shrink-0 pr-10">
                            <DialogTitle>
                                {selectedDocument?.document ?? 'Document'}
                            </DialogTitle>
                        </DialogHeader>
                        {selectedDocument && (
                            <>
                                <div className="flex shrink-0 items-center justify-between gap-3 rounded-md border bg-muted/40 px-3 py-2">
                                    <p className="min-w-0 truncate text-sm text-muted-foreground">
                                        {selectedDocument.file_name}
                                    </p>
                                    <div className="flex shrink-0 items-center gap-1">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            aria-label="Zoom out"
                                            disabled={documentZoom <= 0.5}
                                            onClick={() =>
                                                setDocumentZoom((zoom) =>
                                                    Math.max(0.5, zoom - 0.25),
                                                )
                                            }
                                        >
                                            <Minus className="size-4" />
                                        </Button>
                                        <span className="w-14 text-center text-sm tabular-nums">
                                            {Math.round(documentZoom * 100)}%
                                        </span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            aria-label="Zoom in"
                                            disabled={documentZoom >= 3}
                                            onClick={() =>
                                                setDocumentZoom((zoom) =>
                                                    Math.min(3, zoom + 0.25),
                                                )
                                            }
                                        >
                                            <Plus className="size-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            aria-label="Fit document to window"
                                            onClick={() => setDocumentZoom(1)}
                                        >
                                            <Maximize2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                                <div className="min-h-0 flex-1 overflow-auto rounded-md border bg-black/5">
                                    <div
                                        className="relative min-h-full min-w-full"
                                        style={{
                                            width: `${documentZoom * 100}%`,
                                            height: `${documentZoom * 100}%`,
                                        }}
                                    >
                                        <img
                                            src={selectedDocument.view_url}
                                            alt={`${selectedDocument.document}: ${selectedDocument.file_name}`}
                                            className="absolute inset-0 size-full object-contain"
                                        />
                                    </div>
                                </div>
                            </>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

AthleteShow.layout = {
    breadcrumbs: [
        {
            title: 'Athletes',
            href: index(),
        },
    ],
};
