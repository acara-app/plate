import onboarding from '@/routes/onboarding';
import { Profile, SexOption } from '@/types';
import { Form, Head } from '@inertiajs/react';
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

interface Props {
    profile: Profile;
    sexOptions: SexOption[];
}

export default function Biometrics({ profile, sexOptions }: Props) {
    return (
        <>
            <Head title="Biometrics - Step 1 of 5" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
                <div className="w-full max-w-md">
                    {/* Progress Bar */}
                    <div className="mb-8">
                        <div className="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                            <span>Step 1 of 5</span>
                            <span>20%</span>
                        </div>
                        <div className="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div className="h-2 w-1/5 rounded-full bg-blue-600"></div>
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            Tell us about yourself
                        </h1>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            We'll use this information to calculate your
                            nutritional needs
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
                                        <Label htmlFor="age">Age</Label>
                                        <Input
                                            id="age"
                                            type="number"
                                            name="age"
                                            defaultValue={profile?.age || ''}
                                            min="13"
                                            max="120"
                                            required
                                            placeholder="Enter your age"
                                        />
                                        <InputError message={errors.age} />
                                    </div>

                                    {/* Height */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="height">
                                            Height (cm)
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
                                            placeholder="Enter your height in centimeters"
                                        />
                                        <InputError message={errors.height} />
                                    </div>

                                    {/* Weight */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="weight">
                                            Weight (kg)
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
                                            placeholder="Enter your weight in kilograms"
                                        />
                                        <InputError message={errors.weight} />
                                    </div>

                                    {/* Sex */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="sex">
                                            Biological Sex
                                        </Label>
                                        <Select
                                            name="sex"
                                            defaultValue={profile?.sex || ''}
                                            required
                                        >
                                            <SelectTrigger id="sex">
                                                <SelectValue placeholder="Select your biological sex" />
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
                                            Used for accurate calorie
                                            calculations
                                        </p>
                                    </div>

                                    {/* Submit Button */}
                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full sm:w-auto"
                                        >
                                            {processing && (
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                            )}
                                            Continue to Goals
                                        </Button>
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
