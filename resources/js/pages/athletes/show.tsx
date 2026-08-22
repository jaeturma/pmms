import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, TriangleAlert } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    photo_url: string | null;
    sports_photo_url: string | null;
    sports: string;
    accreditation_status: string;
    can_update: boolean;
};

type Props = {
    athlete: Athlete;
};

export default function AthleteShow({ athlete }: Props) {
    const fullName = `${athlete.first_name} ${athlete.last_name}`;

    const fields: Array<[string, string]> = [
        ['Sex', athlete.sex_label],
        ['Birthdate', `${athlete.birthdate} (age ${athlete.age})`],
        ['LRN', athlete.lrn],
        ['Grade level', `Grade ${athlete.grade_level}`],
        ['Sport', athlete.sports || 'Not assigned'],
        ['Accreditation status', athlete.accreditation_status],
        ['School', athlete.school],
        ['Meet', athlete.meet],
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
                            <dl className="grid gap-3 sm:grid-cols-2">
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
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-1">
                        <Card>
                            <CardHeader>
                                <CardTitle>Passport / Profile Photo</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {athlete.photo_url ? (
                                    <img
                                        src={athlete.photo_url}
                                        alt={`Profile photo of ${fullName}`}
                                        className="max-h-64 w-full rounded-lg object-contain"
                                        loading="lazy"
                                    />
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No profile photo on file.
                                    </p>
                                )}
                                <p className="mt-3 flex items-center gap-2 text-sm">
                                    {athlete.photo_url ? (
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
