let carried = '';

export function carryEmail(email: string): void {
    carried = email.trim().toLowerCase();
}

export function carriedEmail(): string {
    return carried;
}
