<x-default-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        <a
            href="{{ url()->previous() === request()->url() ? '/' : url()->previous() }}"
            class="-mt-10 mb-12 flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
            wire:navigate
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back</span>
        </a>
        <div class="mt-6">
            <div class="prose prose-slate dark:prose-invert mx-auto max-w-4xl">
                <h1>Terms of Service</h1>
                <p><strong>Last Updated: 24 Oct 2024</strong></p>
                <p>
                    Welcome to CustomNutriAI, a web platform designed to provide personalized nutrition guidance through
                    AI-driven meal plans and dietary tools. By accessing or using our website, applications, and
                    services (collectively, the “Services”), you agree to be bound by these Terms of Service (“Terms”).
                </p>

                <h2>1. Acceptance of Terms</h2>
                <p>
                    By creating an account or using the Services, you agree to these Terms. If you do not agree, you may
                    not use the Services. CustomNutriAI may update these Terms at its discretion, and your continued use
                    after changes indicates acceptance of the updated Terms.
                </p>

                <h2>2. Use of Services</h2>
                <p>
                    <strong>a. Eligibility:</strong>
                    You must be at least 18 years old to use the Services due to the collection of sensitive health
                    data, in compliance with applicable laws, including the General Data Protection Regulation (GDPR)
                    for EU users. You confirm you are of legal age to enter a binding contract.
                </p>
                <p>
                    <strong>b. Account:</strong>
                    You may need an account to access features like the personalized nutrition questionnaire. You are
                    responsible for keeping your account and password confidential and for all activities under your
                    account.
                </p>
                <p>
                    <strong>c. Acceptable Use:</strong>
                    You agree not to use the Services for unlawful purposes, to harm or impair the Services, or to
                    interfere with others’ use. You must not submit false or misleading information in the
                    questionnaire, as this could affect the accuracy of nutrition recommendations.
                </p>

                <h2>3. User Data</h2>
                <p>
                    <strong>a. Your Data:</strong>
                    You are responsible for the data you provide, such as biometrics, dietary preferences, and health
                    conditions, through the questionnaire. You must ensure compliance with applicable laws, including
                    GDPR for EU residents. CustomNutriAI does not verify the accuracy of your data.
                </p>
                <p>
                    <strong>b. Data Usage:</strong>
                    By submitting data, you grant CustomNutriAI a non-exclusive, worldwide, royalty-free license to use,
                    store, and process your data to provide personalized nutrition services, such as meal plans and
                    dietary recommendations. This license ends when you delete your data or account, unless the data is
                    anonymized or shared in aggregate form.
                </p>
                <p>
                    <strong>c. Data Privacy:</strong>
                    CustomNutriAI protects your data under GDPR and other applicable laws. Your data (e.g., health
                    conditions, dietary preferences) is stored securely and used only for generating personalized
                    nutrition recommendations. See our Privacy Policy for details on your rights and data handling.
                </p>
                <p>
                    <strong>d. Prohibited Data:</strong>
                    You may not submit data that is illegal, misleading, harmful, or violates third-party rights,
                    including false health information. CustomNutriAI may remove any data that violates these Terms.
                </p>
                <p>
                    <strong>e. Reporting Issues:</strong>
                    If you believe any data on CustomNutriAI infringes your rights, contact us with a detailed notice,
                    and we will take appropriate action.
                </p>

                <h2>4. Intellectual Property</h2>
                <p>
                    The Services, including their features and content (excluding your data), are owned by CustomNutriAI
                    and its licensors. You may not copy, modify, or distribute the Services without permission.
                </p>

                <h2>5. Health and Nutrition Disclaimer</h2>
                <p>
                    CustomNutriAI is not a medical service and does not provide professional medical or dietary advice.
                    AI-generated meal plans and recommendations are for informational purposes only and should not
                    replace advice from a qualified healthcare professional. Consult a doctor or dietitian before making
                    dietary changes, especially if you have health conditions like diabetes or allergies.
                </p>

                <h2>6. Termination</h2>
                <p>
                    CustomNutriAI may terminate or suspend your access to the Services without notice for any reason,
                    including if you breach these Terms or provide inaccurate data that could affect recommendation
                    safety.
                </p>

                <h2>7. Disclaimers and Limitations of Liability</h2>
                <p>
                    The Services are provided “as is” without warranties, including merchantability, fitness for a
                    particular purpose, or non-infringement. CustomNutriAI is not liable for any indirect, incidental,
                    special, consequential, or punitive damages from your use of the Services, including reliance on
                    AI-generated recommendations.
                </p>

                <h2>8. Governing Law</h2>
                <p>
                    These Terms are governed by the laws of [Insert Jurisdiction, e.g., Delaware, USA], and, where
                    applicable, GDPR for EU users, without regard to conflict of law provisions.
                </p>

                <h2>9. Changes</h2>
                <p>
                    CustomNutriAI may update these Terms at any time. Significant changes will be communicated via the
                    Services or email.
                </p>

                <h2>10. Contact Us</h2>
                <p>
                    For questions about these Terms, contact us at
                    <a href="mailto:team@customnutriai.com">team@customnutriai.com</a>.
                </p>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
