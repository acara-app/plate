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
                <p><strong>Last Updated: 19 Nov 2025</strong></p>
                <p>
                    Welcome to Acara Plate, a web platform designed to provide personalized nutrition guidance through
                    AI-driven meal plans and dietary tools. By accessing or using our website, applications, and
                    services (collectively, the “Services”), you agree to be bound by these Terms of Service (“Terms”).
                </p>

                <h2>1. Acceptance of Terms</h2>
                <p>
                    By creating an account or using the Services, you agree to these Terms. If you do not agree, you may
                    not use the Services. Acara Plate may update these Terms at its discretion, and your continued use
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
                    GDPR for EU residents. Acara Plate does not verify the accuracy of your data.
                </p>
                <p>
                    <strong>b. Data Usage:</strong>
                    By submitting data, you grant Acara Plate a non-exclusive, worldwide, royalty-free license to use,
                    store, and process your data to provide personalized nutrition services, such as meal plans and
                    dietary recommendations. This license ends when you delete your data or account, unless the data is
                    anonymized or shared in aggregate form.
                </p>
                <p>
                    <strong>c. Data Privacy:</strong>
                    Acara Plate protects your data under GDPR and other applicable laws. Your data (e.g., health
                    conditions, dietary preferences) is stored securely and used only for generating personalized
                    nutrition recommendations. See our Privacy Policy for details on your rights and data handling.
                </p>
                <p>
                    <strong>d. Prohibited Data:</strong>
                    You may not submit data that is illegal, misleading, harmful, or violates third-party rights,
                    including false health information. Acara Plate may remove any data that violates these Terms.
                </p>
                <p>
                    <strong>e. Reporting Issues:</strong>
                    If you believe any data on Acara Plate infringes your rights, contact us with a detailed notice,
                    and we will take appropriate action.
                </p>

                <h2>4. Intellectual Property</h2>
                <p>
                    The Services, including their features and content (excluding your data), are owned by Acara Plate
                    and its licensors. You may not copy, modify, or distribute the Services without permission.
                </p>

                <h2>5. Health and Nutrition Disclaimer</h2>
                <p>
                    <strong>Not Medical Advice:</strong> Acara Plate is not a medical service and does not provide professional medical, diagnosis, or treatment advice. The Services, including AI-generated meal plans, nutritional insights, and glucose tracking, are for informational and educational purposes only.
                </p>
                <p>
                    <strong>Consult a Professional:</strong> Always seek the advice of your physician, registered dietitian, or other qualified health provider with any questions you may have regarding a medical condition, dietary changes, or blood glucose management. Never disregard professional medical advice or delay in seeking it because of something you have read on the Services.
                </p>
                <p>
                    <strong>AI Limitations:</strong> The meal plans and nutritional data are generated by Artificial Intelligence. While we strive for accuracy, AI models can occasionally produce incorrect information regarding allergens, ingredients, or macronutrient values. You are responsible for verifying all ingredients and nutritional content independently before consumption.
                </p>

                <h2>6. Termination</h2>
                <p>
                    Acara Plate may terminate or suspend your access to the Services without notice for any reason,
                    including if you breach these Terms or provide inaccurate data that could affect recommendation
                    safety.
                </p>

                <h2>7. Disclaimers and Limitations of Liability</h2>
                <p>
                    The Services are provided “as is” without warranties of any kind, whether express or implied, including merchantability, fitness for a particular purpose, or non-infringement.
                </p>
                <p>
                    <strong>No Liability:</strong> To the fullest extent permitted by law, Acara Plate, its authors, contributors, and licensors shall not be liable for any direct, indirect, incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly, or any loss of data, use, goodwill, or other intangible losses, resulting from (a) your access to or use of or inability to access or use the Services; (b) any conduct or content of any third party on the Services; or (c) unauthorized access, use, or alteration of your transmissions or content.
                </p>

                <h2>8. Governing Law</h2>
                <p>
                    These Terms are governed by the laws of [Insert Jurisdiction, e.g., Delaware, USA], and, where
                    applicable, GDPR for EU users, without regard to conflict of law provisions.
                </p>

                <h2>9. Changes</h2>
                <p>
                    Acara Plate may update these Terms at any time. Significant changes will be communicated via the
                    Services or email.
                </p>

                <h2>10. Contact Us</h2>
                <p>
                    For questions about these Terms, contact us at
                    <a href="mailto:team@acara.app">team@acara.app</a>.
                </p>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
