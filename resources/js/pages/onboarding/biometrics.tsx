import { dashboard } from '@/routes';
import onboarding from '@/routes/onboarding';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { BloodTypeOption, Profile, SexOption } from '@/types/user';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

function calculateAge(dateOfBirth: string): number | null {
    const birth = new Date(dateOfBirth);
    if (isNaN(birth.getTime())) {
        return null;
    }

    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (
        monthDiff < 0 ||
        (monthDiff === 0 && today.getDate() < birth.getDate())
    ) {
        age--;
    }

    return age;
}

interface Props {
    profile: Profile;
    sexOptions: SexOption[];
    bloodTypeOptions: BloodTypeOption[];
}

export default function Biometrics({
    profile,
    sexOptions,
    bloodTypeOptions,
}: Props) {
    const { t } = useTranslation('common');
    const [dateOfBirth, setDateOfBirth] = useState(
        profile?.date_of_birth ?? '',
    );
    const [age, setAge] = useState<string>(profile?.age?.toString() ?? '');

    const handleDateOfBirthChange = (value: string) => {
        setDateOfBirth(value);
        const computed = calculateAge(value);
        if (computed !== null && computed >= 0) {
            setAge(computed.toString());
        }
    };

    return (
        <>
            <Head title={t('onboarding.biometrics.title')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-md">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>
                                {t('onboarding.biometrics.step', {
                                    current: 1,
                                    total: 3,
                                })}
                            </span>
                            <span>33%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="relative h-2 w-[33%] overflow-hidden rounded-full bg-primary shadow-[0_0_12px_rgba(16,185,129,0.4)]">
                                <div className="absolute inset-0 bg-linear-to-r from-white/30 via-transparent to-transparent"></div>
                                <div className="absolute inset-0 bg-linear-to-l from-black/10 via-transparent to-white/10"></div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {t('onboarding.biometrics.heading')}
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            {t('onboarding.biometrics.description')}
                        </p>

                        <Form
                            {...onboarding.biometrics.store.form()}
                            disableWhileProcessing
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Age */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="age">
                                            {t('onboarding.biometrics.age')}
                                        </Label>
                                        <Input
                                            id="age"
                                            type="number"
                                            name="age"
                                            value={age}
                                            onChange={(e) =>
                                                setAge(e.target.value)
                                            }
                                            min="13"
                                            max="120"
                                            required
                                            placeholder={t(
                                                'onboarding.biometrics.age_placeholder',
                                            )}
                                        />
                                        <InputError message={errors.age} />
                                    </div>

                                    {/* Date of Birth (optional) */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="date_of_birth">
                                            {t(
                                                'onboarding.biometrics.date_of_birth',
                                            )}
                                        </Label>
                                        <Input
                                            id="date_of_birth"
                                            type="date"
                                            name="date_of_birth"
                                            value={dateOfBirth}
                                            onChange={(e) =>
                                                handleDateOfBirthChange(
                                                    e.target.value,
                                                )
                                            }
                                            max={
                                                new Date()
                                                    .toISOString()
                                                    .split('T')[0]
                                            }
                                        />
                                        <InputError
                                            message={errors.date_of_birth}
                                        />
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            {t(
                                                'onboarding.biometrics.date_of_birth_help',
                                            )}
                                        </p>
                                    </div>

                                    {/* Height */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="height">
                                            {t('onboarding.biometrics.height')}
                                        </Label>
                                        <Input
                                            id="height"
                                            type="number"
                                            name="height"
                                            defaultValue={profile?.height || ''}
                                            step="0.01"
                                            min="50"
                                            max="300"
                                            required
                                            placeholder={t(
                                                'onboarding.biometrics.height_placeholder',
                                            )}
                                        />
                                        <InputError message={errors.height} />
                                    </div>

                                    {/* Weight */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="weight">
                                            {t('onboarding.biometrics.weight')}
                                        </Label>
                                        <Input
                                            id="weight"
                                            type="number"
                                            name="weight"
                                            defaultValue={profile?.weight || ''}
                                            step="0.01"
                                            min="20"
                                            max="500"
                                            required
                                            placeholder={t(
                                                'onboarding.biometrics.weight_placeholder',
                                            )}
                                        />
                                        <InputError message={errors.weight} />
                                    </div>

                                    {/* Sex */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="sex">
                                            {t('onboarding.biometrics.sex')}
                                        </Label>
                                        <Select
                                            name="sex"
                                            defaultValue={profile?.sex || ''}
                                            required
                                        >
                                            <SelectTrigger id="sex">
                                                <SelectValue
                                                    placeholder={t(
                                                        'onboarding.biometrics.sex_placeholder',
                                                    )}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {sexOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.sex} />
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            {t(
                                                'onboarding.biometrics.sex_help',
                                            )}
                                        </p>
                                    </div>

                                    {/* Blood Type */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="blood_type">
                                            {t(
                                                'onboarding.biometrics.blood_type',
                                            )}
                                        </Label>
                                        <Select
                                            name="blood_type"
                                            defaultValue={
                                                profile?.blood_type || ''
                                            }
                                        >
                                            <SelectTrigger id="blood_type">
                                                <SelectValue
                                                    placeholder={t(
                                                        'onboarding.biometrics.blood_type_placeholder',
                                                    )}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {bloodTypeOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.blood_type}
                                        />
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            {t(
                                                'onboarding.biometrics.blood_type_help',
                                            )}
                                        </p>
                                    </div>

                                    {/* Footer Section */}
                                    <div className="flex flex-col items-center gap-4">
                                        {/* Action Buttons */}
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="min-w-30"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            {t(
                                                'onboarding.biometrics.continue',
                                            )}
                                        </Button>

                                        {/* Exit Link - Centered Below */}
                                        <Link
                                            href={dashboard.url()}
                                            className="text-sm text-gray-600 hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                        >
                                            {t('onboarding.biometrics.exit')}
                                        </Link>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </>
    );
}
