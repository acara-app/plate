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
                <h1>Privacy Policy</h1>
                <p><strong>Effective Date: October 25, 2025</strong></p>
                <p>
                    At Acara Plate, we value your privacy and are committed to protecting your personal data. This
                    Privacy Policy explains how we collect, use, store, and protect the information you provide through
                    our website, applications, and services (collectively, the “Services”), including our personalized
                    nutrition questionnaire. By using the Services, you agree to this Privacy Policy.
                </p>

                <h2>1. Information We Collect</h2>
                <p>We collect the following types of information to provide personalized nutrition guidance:</p>
                <p>
                    <strong>a. Personal Data:</strong>
                    When you complete our nutrition questionnaire or create an account, we may collect:
                <ul>
                    <li><strong>Biometrics:</strong> Age, height, weight, sex.</li>
                    <li><strong>Goals:</strong> Nutrition goals (e.g., weight loss, muscle gain, managing health
                        conditions).</li>
                    <li><strong>Lifestyle:</strong> Activity level, sleep patterns, occupation.</li>
                    <li><strong>Dietary Preferences:</strong> Allergies, intolerances (e.g., gluten, lactose), dietary
                        patterns (e.g., vegan, keto), disliked foods.</li>
                    <li><strong>Health Conditions:</strong> Medical conditions (e.g., diabetes, high blood pressure).
                    </li>
                    <li><strong>Contact Information:</strong> Email address (for account management).</li>
                </ul>
                </p>
                <p>
                    <strong>b. Non-Personal Data:</strong>
                    We may collect anonymized or aggregated data, such as usage statistics (e.g., pages visited, time
                    spent on the Services), to improve the platform.
                </p>
                <p>
                    <strong>c. Cookies and Tracking:</strong>
                    We use cookies and similar technologies to enhance your experience, analyze usage, and improve our
                    Services. You can manage cookie preferences through your browser settings.
                </p>

                <h2>2. How We Use Your Information</h2>
                <p>We use your data to provide and improve the Services, including:</p>
                <ul>
                    <li>Generating personalized meal plans and dietary recommendations based on your questionnaire
                        responses.</li>
                    <li>Filtering meal plans to accommodate dietary restrictions (e.g., allergies, vegan diets).</li>
                    <li>Managing your account and communicating with you (e.g., service updates).</li>
                    <li>Analyzing anonymized data to enhance the Services and develop new features.</li>
                </ul>
                <p>Your data will not be used for purposes other than those described in this Privacy Policy without
                    your consent.</p>

                <h2>3. Legal Basis for Processing (GDPR)</h2>
                <p>For users in the European Union, we process personal data under the following legal bases:</p>
                <ul>
                    <li><strong>Consent:</strong> You provide consent by submitting the questionnaire or creating an
                        account.</li>
                    <li><strong>Contract:</strong> Processing is necessary to deliver the Services (e.g., generating
                        meal plans).</li>
                    <li><strong>Legitimate Interests:</strong> We use anonymized data to improve the Services, ensuring
                        minimal impact on your privacy.</li>
                </ul>

                <h2>4. Data Sharing</h2>
                <p>We do not sell or share your personal data with third parties, except:</p>
                <ul>
                    <li>With service providers (e.g., hosting providers) who process data on our behalf, under strict
                        confidentiality agreements.</li>
                    <li>When required by law (e.g., to comply with legal obligations or protect user safety).</li>
                    <li>In anonymized or aggregated form for analytics or research to improve the Services.</li>
                </ul>

                <h2>5. Data Security</h2>
                <p>
                    We implement industry-standard security measures, such as encryption and secure servers, to protect
                    your data. However, no system is completely secure, and we cannot guarantee absolute security. You
                    are responsible for maintaining the confidentiality of your account credentials.
                </p>

                <h2>6. Data Retention</h2>
                <p>
                    We retain your personal data only as long as necessary to provide the Services or comply with legal
                    obligations. You can request deletion of your data at any time (see Section 7). Anonymized data may
                    be retained indefinitely for analytics purposes.
                </p>

                <h2>7. Your Rights (GDPR and Applicable Laws)</h2>
                <p>You have the following rights regarding your personal data:</p>
                <ul>
                    <li><strong>Access:</strong> Request a copy of your data.</li>
                    <li><strong>Rectification:</strong> Correct inaccurate data.</li>
                    <li><strong>Deletion:</strong> Request deletion of your data.</li>
                    <li><strong>Restriction:</strong> Limit how we process your data.</li>
                    <li><strong>Portability:</strong> Receive your data in a structured, machine-readable format.</li>
                    <li><strong>Objection:</strong> Object to processing based on legitimate interests.</li>
                    <li><strong>Withdraw Consent:</strong> Revoke consent at any time, without affecting prior
                        processing.</li>
                </ul>
                <p>
                    To exercise these rights, contact us at <a
                        href="mailto:team@acara.app">team@acara.app</a>. We will respond within 30 days,
                    as required by GDPR.
                </p>

                <h2>8. International Data Transfers</h2>
                <p>
                    If you are outside Canada, your data may be transferred to and processed on servers located in Canada. When transferring personal data originating from the European Union or other jurisdictions with data transfer restrictions, we rely on applicable safeguards, including Standard Contractual Clauses or other lawful mechanisms, to ensure your information remains protected and compliant with governing privacy laws.
                </p>

                <h2>9. Third-Party Links</h2>
                <p>
                    The Services may contain links to third-party websites (e.g., nutrition resources). We are not
                    responsible for their privacy practices. Review their policies before sharing data.
                </p>

                <h2>10. Health and Nutrition Disclaimer</h2>
                <p>
                    Acara Plate is not a medical service. AI-generated meal plans and recommendations are for
                    informational purposes only and should not replace professional medical or dietary advice. Consult a
                    healthcare professional before making dietary changes, especially if you have health conditions.
                </p>

                <h2>11. Changes to This Privacy Policy</h2>
                <p>
                    We may update this Privacy Policy to reflect changes in our practices or legal requirements. Updates
                    will be posted on this page, and significant changes will be communicated via the Services or email.
                </p>

                <h2>12. Contact Us</h2>
                <p>
                    If you have questions about this Privacy Policy or your data, contact us at
                    <a href="mailto:team@acara.app">team@acara.app</a>.
                </p>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
