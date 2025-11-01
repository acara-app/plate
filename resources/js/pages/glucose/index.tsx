import GlucoseReadingController from '@/actions/App/Http/Controllers/GlucoseReadingController';
import AdminPageWrap from '@/components/sections/admin-page-wrap';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { BarChart3, Pencil, Plus, Trash2 } from 'lucide-react';
import GlucoseReadingDialog from './glucose-reading-dialog';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Glucose Tracking',
        href: GlucoseReadingController.index().url,
    },
];

interface ReadingType {
    value: string;
    label: string;
}

interface GlucoseReading {
    id: number;
    reading_value: number;
    reading_type: string;
    measured_at: string;
    notes: string | null;
    created_at: string;
}

interface Props {
    readings: {
        data: GlucoseReading[];
        current_page: number;
        last_page: number;
    };
    readingTypes: ReadingType[];
}

export default function GlucoseIndex({ readings, readingTypes }: Props) {
    const createModal = useModalToggle();
    const editModal = useModalValueToggle<GlucoseReading>();

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this reading?')) {
            router.delete(GlucoseReadingController.destroy(id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Glucose Tracking" />
            <AdminPageWrap variant="lg">
                <div className="space-y-6">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Glucose Tracking
                            </h1>
                            <p className="text-muted-foreground">
                                Track and monitor your blood glucose levels
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <a
                                    href={
                                        GlucoseReadingController.dashboard().url
                                    }
                                >
                                    <BarChart3 className="mr-2 size-4" />
                                    View Dashboard
                                </a>
                            </Button>
                            <Button onClick={() => createModal.open()}>
                                <Plus className="mr-2 size-4" />
                                Add Reading
                            </Button>
                        </div>
                    </div>

                    <GlucoseReadingDialog
                        mode="create"
                        open={createModal.isOpen}
                        onOpenChange={(open) =>
                            open ? createModal.open() : createModal.close()
                        }
                        readingTypes={readingTypes}
                    />

                    <GlucoseReadingDialog
                        mode="edit"
                        open={editModal.isOpen}
                        onOpenChange={(open) =>
                            open
                                ? editModal.open(editModal.state!)
                                : editModal.close()
                        }
                        readingTypes={readingTypes}
                        reading={editModal.state}
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle>Your Readings</CardTitle>
                            <CardDescription>
                                Recent glucose measurements
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {readings.data.length === 0 ? (
                                <div className="py-8 text-center text-muted-foreground">
                                    No readings yet. Add your first glucose
                                    reading to get started.
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {readings.data.map((reading) => (
                                        <div
                                            key={reading.id}
                                            className="flex items-start justify-between rounded-lg border p-4"
                                        >
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-2xl font-bold">
                                                        {reading.reading_value}
                                                    </span>
                                                    <span className="text-sm text-muted-foreground">
                                                        mg/dL
                                                    </span>
                                                    <span className="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                                        {reading.reading_type}
                                                    </span>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {new Date(
                                                        reading.measured_at,
                                                    ).toLocaleString()}
                                                </p>
                                                {reading.notes && (
                                                    <p className="text-sm">
                                                        {reading.notes}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        editModal.open(reading)
                                                    }
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        handleDelete(reading.id)
                                                    }
                                                >
                                                    <Trash2 className="size-4 text-red-500" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AdminPageWrap>
        </AppLayout>
    );
}
