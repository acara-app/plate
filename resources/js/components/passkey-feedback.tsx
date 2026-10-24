import InputError from '@/components/input-error';
import {
    InvalidDomainError,
    NotSupportedError,
    PasskeyExistsError,
    UserCancelledError,
} from '@laravel/passkeys';
import { useTranslation } from 'react-i18next';

export default function PasskeyFeedback({ error }: { error: Error | null }) {
    const { t } = useTranslation('auth');

    if (!error) {
        return null;
    }

    const key =
        error instanceof UserCancelledError
            ? 'cancelled'
            : error instanceof PasskeyExistsError
              ? 'already_registered'
              : error instanceof NotSupportedError
                ? 'unsupported'
                : error instanceof InvalidDomainError
                  ? 'invalid_domain'
                  : 'failed';

    return <InputError role="status" message={t(`passkeys.${key}`)} />;
}
