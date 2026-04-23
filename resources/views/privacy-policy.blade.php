@section('title', 'Privacy Policy | How We Protect Your Health Data')
@section('meta_description', 'Your privacy is paramount. Read the Acara Plate Privacy Policy to understand how we collect, use, and protect your biometrics, health conditions, and dietary data.')

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
                <p><strong>Effective Date: April 23, 2026</strong></p>
                <p>
                    At Acara Plate, we value your privacy and are committed to protecting your personal data. This
                    Privacy Policy explains how we collect, use, store, and protect the information you provide through
                    our website, applications, and services (collectively, the “Services”), including our personalized
                    nutrition questionnaire and the Acara Health Sync companion app for iOS. By using the Services, you agree to this Privacy Policy.
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
                    <li><strong>Additional Goals (free-text):</strong> An optional free-text field where you describe goals in your own words. Because this field accepts any text, you should avoid including sensitive medical details you do not wish processed by AI providers (see Section 5).</li>
                    <li><strong>Lifestyle:</strong> Activity level, sleep patterns, occupation.</li>
                    <li><strong>Dietary Preferences:</strong> Allergies, intolerances (e.g., gluten, lactose), dietary
                        patterns (e.g., vegan, keto), disliked foods.</li>
                    <li><strong>Health Conditions:</strong> Medical conditions (e.g., diabetes, high blood pressure).
                    </li>
                    <li><strong>Contact Information:</strong> Email address (for account management).</li>
                </ul>
                </p>
                <p>
                    <strong>b. Apple Health Data (via Acara Health Sync):</strong>
                    If you use the Acara Health Sync companion app for iOS, we may collect health and fitness data from Apple Health with your explicit permission. This includes:
                <ul>
                    <li><strong>Glucose:</strong> Blood glucose readings from continuous glucose monitors and manual entries.</li>
                    <li><strong>Vitals:</strong> Heart rate, heart rate variability, blood pressure, blood oxygen saturation, respiratory rate.</li>
                    <li><strong>Body Measurements:</strong> Weight, BMI, body fat percentage, height.</li>
                    <li><strong>Activity:</strong> Steps, active energy burned, workouts, cycling, swimming.</li>
                    <li><strong>Sleep:</strong> Sleep stages (REM, Core, Deep), time in bed.</li>
                    <li><strong>Nutrition:</strong> Caloric intake, macronutrients, vitamins, minerals, water intake.</li>
                    <li><strong>Other:</strong> Mobility, reproductive health, hearing, mindfulness, and medications data.</li>
                </ul>
                This data is collected on a read-only basis. Acara Health Sync does not write any data to Apple Health. All Apple Health data is encrypted on your device before transmission and sent directly to your Acara Plate instance. Apple Health data is used solely for the purpose of providing personalized nutrition guidance and health insights through Altani, your AI health coach. We do not use Apple Health data for advertising, marketing, or data mining purposes.
                </p>
                <p>
                    <strong>c. Non-Personal Data:</strong>
                    We may collect anonymized or aggregated data, such as usage statistics (e.g., pages visited, time
                    spent on the Services), to improve the platform.
                </p>
                <p>
                    <strong>d. Cookies and Tracking:</strong>
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
                    your consent. See Section 5 for the specific data flows involved in AI-assisted features.</p>

                <h2>3. Legal Basis for Processing (GDPR)</h2>
                <p>For users in the European Union, we process personal data under the following legal bases:</p>
                <ul>
                    <li><strong>Consent:</strong> You provide consent by submitting the questionnaire or creating an
                        account.</li>
                    <li><strong>Explicit Consent (Article 9):</strong> For special-category data (health conditions, medications, biometrics, reproductive and other health-related fields), we rely on your explicit consent, obtained when you submit that information in the questionnaire or onboarding. You may withdraw this consent at any time (see Section 8).</li>
                    <li><strong>Contract:</strong> Processing is necessary to deliver the Services (e.g., generating
                        meal plans).</li>
                    <li><strong>Legitimate Interests:</strong> We use anonymized data to improve the Services, ensuring
                        minimal impact on your privacy.</li>
                </ul>

                <h2>4. Data Sharing</h2>
                <p>We do not sell your personal data. We share it only with:</p>
                <ul>
                    <li><strong>Service providers</strong> we engage to operate the Services, including hosting providers, email delivery, and <strong>AI service providers</strong> who process your data to generate responses, summaries, embeddings, and transcriptions (see Section 5 for the current list and the categories of data sent). All service providers are bound by data-processing agreements and confidentiality obligations.</li>
                    <li>When required by law (e.g., to comply with legal obligations or protect user safety).</li>
                    <li>In anonymized or aggregated form for analytics or research to improve the Services.</li>
                </ul>

                <h2>5. AI Processing and Third-Party LLM Providers</h2>
                <p>
                    Acara Plate uses third-party artificial intelligence (AI) service providers to power features such as Altani (your AI health coach), meal-plan generation, glucose insights, conversation summaries, and other AI-assisted guidance. When you use these features, a limited and purpose-bound subset of your personal data is transmitted to these providers for inference.
                </p>
                <p>
                    <strong>a. Providers we currently use:</strong>
                <ul>
                    <li><strong>OpenAI</strong> — conversational chat (the primary Altani chat agent, which currently defaults to a GPT-5-family model), conversation summarization, audio transcription, and text embeddings.</li>
                    <li><strong>Google</strong> — meal-plan generation and day-by-day meal suggestion synthesis, via Gemini and any successor or specialized Google-hosted models (including future variants such as medical- or vision-domain Gemma/Gemini models) served through the same Google API surface.</li>
                </ul>
                We disclose <strong>processors</strong>, not individual models. Swapping to a different model offered by the same processor (e.g., a newer Gemini release, or a specialized medical model hosted by Google) does not change which company receives your data and does not require a separate notice. We will update this list only when a new <strong>processor</strong> (a different company or a self-hosted deployment) is introduced for your data.
                </p>
                <p>
                    <strong>b. Data sent to AI providers:</strong>
                    When you interact with an AI-assisted feature, we transmit:
                <ul>
                    <li>Your chat message text.</li>
                    <li>Prior messages in the current conversation (for context and summarization).</li>
                    <li>A minimized "profile summary" containing your age, height, weight, sex, BMI, estimated daily calorie needs, dietary preferences, structured goals, and diet type. <strong>We do not include your date of birth, blood type, medications, specific medical conditions, or the "household context" field in this summary.</strong></li>
                    <li>Your free-text "Additional Goals" field, <strong>only when the AI explicitly requests your goals</strong> via a dedicated tool call (for example, to answer a workout or wellness question).</li>
                    <li>Your specific medical conditions, medications, or detailed Apple Health readings, <strong>only when the AI explicitly requests them</strong> via a dedicated tool call to answer a health-related question.</li>
                    <li>Image attachments you choose to share with the AI for food or label analysis.</li>
                </ul>
                </p>
                <p>
                    <strong>Conversation retention note:</strong>
                    Once the AI has received any of the above data via a tool call, that data is stored alongside the conversation message as a "tool result" and may be transmitted to the AI provider again on subsequent turns of the same conversation to preserve context. Deleting the conversation removes it from future context. We do not currently scrub tool results from stored conversation history.
                </p>
                <p>
                    <strong>c. What we do not do:</strong>
                <ul>
                    <li>We do not submit your data to any training-eligible product surface under our control. We access providers through their standard API endpoints, not their consumer applications or feedback-labeling interfaces.</li>
                    <li>Beyond what we send, how a provider retains, processes, or uses your submissions is governed by that provider's own API data-use policy — not by additional controls operated by Acara Plate. We do not currently operate zero-retention routing, BAA-eligible endpoints, or region-pinned processing, and we do not independently verify provider-side retention settings. If you require those guarantees, please consult each provider's terms directly before using AI-assisted features.</li>
                    <li>We do not share your data with AI providers for advertising, profiling across services, or any purpose other than generating your requested response.</li>
                    <li>We do not inject your freeform personal notes (such as "Additional Goals" or "Household Context") into AI prompts by default.</li>
                    <li>We do not permit web search by the AI when your active request involves your personal or medical data — in that mode, the AI cannot issue web queries on your behalf.</li>
                </ul>
                </p>
                <p>
                    <strong>d. International transfers:</strong>
                    AI providers process data on servers located outside Canada (primarily the United States and the European Union). Where the providers fall under jurisdictions with data-transfer restrictions, we rely on Standard Contractual Clauses or equivalent safeguards (see Section 9).
                </p>
                <p>
                    <strong>e. Your choices:</strong>
                <ul>
                    <li>You can avoid sending free-text fields (such as "Additional Goals" or "Household Context") to AI providers simply by leaving them blank.</li>
                    <li>You can request deletion of your data at any time (see Section 8).</li>
                    <li>If you prefer not to use AI-assisted features at all, do not initiate chats with Altani or use AI-generated meal plans; non-AI features of the app remain available.</li>
                </ul>
                </p>
                <p>
                    <strong>f. Not a HIPAA-covered service:</strong>
                    Acara Plate is a consumer health and nutrition application. It is not a HIPAA-covered entity and does not claim to provide HIPAA-compliant processing of Protected Health Information. Do not use the Services to transmit PHI in a manner that would require HIPAA protections.
                </p>

                <h2>6. Data Security</h2>
                <p>
                    We implement industry-standard security measures, such as encryption and secure servers, to protect
                    your data. However, no system is completely secure, and we cannot guarantee absolute security. You
                    are responsible for maintaining the confidentiality of your account credentials.
                </p>

                <h2>7. Data Retention</h2>
                <p>
                    We retain your personal data only as long as necessary to provide the Services or comply with legal
                    obligations. You can request deletion of your data at any time (see Section 8). Anonymized data may
                    be retained indefinitely for analytics purposes.
                </p>

                <h2>8. Your Rights (GDPR and Applicable Laws)</h2>
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

                <h2>9. International Data Transfers</h2>
                <p>
                    If you are outside Canada, your data may be transferred to and processed on servers located in Canada. When transferring personal data originating from the European Union or other jurisdictions with data transfer restrictions, we rely on applicable safeguards, including Standard Contractual Clauses or other lawful mechanisms, to ensure your information remains protected and compliant with governing privacy laws.
                </p>

                <h2>10. Third-Party Links</h2>
                <p>
                    The Services may contain links to third-party websites (e.g., nutrition resources). We are not
                    responsible for their privacy practices. Review their policies before sharing data.
                </p>

                <h2>11. Health and Nutrition Disclaimer</h2>
                <p>
                    Acara Plate is not a medical service. AI-generated meal plans, nutritional insights, and glucose tracking features are for informational and educational purposes only and should not replace professional medical or dietary advice. Consult a healthcare professional before making dietary changes, especially if you have health conditions.
                </p>

                <h2>12. Changes to This Privacy Policy</h2>
                <p>
                    We may update this Privacy Policy to reflect changes in our practices or legal requirements. Updates
                    will be posted on this page, and significant changes will be communicated via the Services or email.
                </p>

                <h2>13. Contact Us</h2>
                <p>
                    If you have questions about this Privacy Policy or your data, contact us at
                    <a href="mailto:team@acara.app">team@acara.app</a> or
                    <a href="mailto:support@acara.app">support@acara.app</a>.
                </p>
            </div>
        </div>
    </div>
    <x-footer />
</x-default-layout>
