import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
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

                    <Card>
                        <CardHeader>
                            <CardTitle>Photo</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {athlete.photo_url ? (
                                <img
                                    src={athlete.photo_url}
                                    alt={`Photo of ${fullName}`}
                                    className="max-h-64 w-full rounded-lg object-contain"
                                />
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No photo on file.
                                </p>
                            )}
                        </CardContent>
                    </Card>
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
