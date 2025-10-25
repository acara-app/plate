# Google OAuth Setup Guide

## Google Cloud Console Configuration

Follow these steps to set up Google OAuth for your application:

### 1. Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click on the project dropdown at the top
3. Click "New Project"
4. Enter your project name (e.g., "CustomNutriAI")
5. Click "Create"

### 2. Enable Google+ API

1. In the Google Cloud Console, go to "APIs & Services" > "Library"
2. Search for "Google+ API"
3. Click on it and press "Enable"
4. Also enable "Google People API" for additional user information

### 3. Configure OAuth Consent Screen

1. Go to "APIs & Services" > "OAuth consent screen"
2. Choose "External" user type (unless you have a Google Workspace)
3. Click "Create"
4. Fill in the required information:
   - **App name**: CustomNutriAI
   - **User support email**: Your email address
   - **App logo**: (Optional) Upload your app logo
   - **App domain**: Your application domain
   - **Authorized domains**: Add your domain (e.g., `customnutriai.com`)
   - **Developer contact information**: Your email address
5. Click "Save and Continue"
6. **Scopes**: Click "Add or Remove Scopes"
   - Select: `userinfo.email`
   - Select: `userinfo.profile`
   - Select: `openid`
7. Click "Save and Continue"
8. **Test users** (if in testing mode): Add email addresses that can test the OAuth
9. Click "Save and Continue"
10. Review and click "Back to Dashboard"

### 4. Create OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth client ID"
3. Choose "Web application" as the application type
4. Configure the OAuth client:
   - **Name**: CustomNutriAI Web Client
   - **Authorized JavaScript origins**:
     - `http://localhost:8000` (for local development)
     - `https://customnutriai.test` (for Laravel Herd)
     - `https://yourdomain.com` (for production)
   - **Authorized redirect URIs**:
     - `http://localhost:8000/auth/google/callback`
     - `https://customnutriai.test/auth/google/callback`
     - `https://yourdomain.com/auth/google/callback`
5. Click "Create"
6. **Save your credentials**:
   - Copy the **Client ID**
   - Copy the **Client Secret**

### 5. Configure Your Laravel Application

Add the following to your `.env` file:

```env
GOOGLE_CLIENT_ID=your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret-here
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

For production, update `GOOGLE_REDIRECT_URI` to your production URL:

```env
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

### 6. Publishing Your App (Optional)

If you want to make your app available to all users:

1. Go back to "OAuth consent screen"
2. Click "Publish App"
3. Confirm the publication
4. Google may require a verification process for sensitive scopes

## Testing Your OAuth Integration

### Manual Testing

1. Start your Laravel application:
   ```bash
   php artisan serve
   ```

2. Navigate to: `http://localhost:8000/auth/google/redirect`

3. You should be redirected to Google's OAuth consent screen

4. Sign in with your Google account

5. Grant the requested permissions

6. You should be redirected back to your dashboard

### Automated Testing

Run the OAuth tests:

```bash
php artisan test --filter=oauth
```

## Common Issues & Solutions

### Error: "redirect_uri_mismatch"

**Solution**: Ensure the redirect URI in your `.env` matches exactly with one configured in Google Cloud Console (including http/https, port, and path).

### Error: "Access blocked: This app's request is invalid"

**Solution**: 
- Verify you've enabled the required APIs (Google+ API, People API)
- Check that your OAuth consent screen is properly configured
- Ensure your app is published or the testing user is added

### Error: "invalid_client"

**Solution**: Double-check your `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env` file.

### Users can't sign in (only test users work)

**Solution**: Publish your OAuth app in the Google Cloud Console.

## Security Best Practices

1. **Never commit credentials**: Keep `.env` file out of version control
2. **Use environment-specific credentials**: Different credentials for dev, staging, and production
3. **Rotate secrets regularly**: Change your client secret periodically
4. **Limit redirect URIs**: Only add the exact URIs you need
5. **Monitor usage**: Regularly check the Google Cloud Console for unusual activity
6. **Enable 2FA**: Protect your Google Cloud account with two-factor authentication

## Additional Resources

- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [Google Cloud Console](https://console.cloud.google.com/)
