# Railway Deployment Guide for IdeaOne

## Prerequisites
1. Railway account (https://railway.app)
2. Supabase account (https://supabase.com)
3. Razorpay account (https://razorpay.com)
4. GitHub account

## Step 1: Prepare Your Code

1. Ensure all files are committed to your GitHub repository
2. Verify .env file is NOT committed (it should be in .gitignore)
3. Verify .env.example is committed

## Step 2: Set Up Supabase

1. Create a new project at https://supabase.com
2. Go to Settings > Database
3. Note down:
   - Database Host (from Connection string)
   - Database Password
   - Connection string (URI format)

4. Execute the database schema:
   - Go to SQL Editor in Supabase
   - Copy contents of `database/supabase-schema.sql`
   - Paste and execute

5. Set up Storage buckets:
   - Go to Storage in Supabase
   - Create bucket: `idea_uploads` (public: false)
   - Create bucket: `avatars` (public: true)

6. Get API keys:
   - Go to Settings > API
   - Copy `anon` key (SUPABASE_ANON_KEY)
   - Copy `service_role` key (SUPABASE_SERVICE_ROLE_KEY)
   - Copy Project URL (SUPABASE_URL)

## Step 3: Set Up Razorpay

1. Create account at https://razorpay.com
2. Go to Settings > API Keys
3. Generate Key Id and Key Secret
4. Set up webhook for payment verification (optional)

## Step 4: Deploy to Railway

### Option A: From GitHub (Recommended)

1. Go to https://railway.app/new
2. Select "Deploy from GitHub repo"
3. Select your `aesimo/crispy-memory` repository
4. Railway will detect it as a PHP application

### Option B: Via CLI

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Initialize project
railway init

# Add variables
railway variables set DB_HOST=your-project.supabase.co
railway variables set DB_PORT=5432
railway variables set DB_NAME=postgres
railway variables set DB_USER=postgres
railway variables set DB_PASSWORD=your-database-password
railway variables set SUPABASE_URL=https://your-project.supabase.co
railway variables set SUPABASE_ANON_KEY=your-anon-key
railway variables set SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
railway variables set RAZORPAY_KEY_ID=your-key-id
railway variables set RAZORPAY_KEY_SECRET=your-key-secret
railway variables set APP_ENV=production
railway variables set APP_URL=https://your-app-name.railway.app
railway variables set JWT_SECRET=strong-random-secret-key

# Deploy
railway up
```

## Step 5: Configure Environment Variables

In Railway Dashboard > Settings > Variables, add:

```bash
# Database
DB_HOST=your-project.supabase.co
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASSWORD=your-database-password

# Supabase
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key

# Razorpay
RAZORPAY_KEY_ID=your-razorpay-key-id
RAZORPAY_KEY_SECRET=your-razorpay-key-secret
RAZORPAY_WEBHOOK_SECRET=your-webhook-secret

# Application
APP_NAME=IdeaOne
APP_URL=https://your-app-name.railway.app
APP_ENV=production
JWT_SECRET=strong-random-secret-key-change-this

# Email (optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password

# File Upload
MAX_FILE_SIZE=10485760
ALLOWED_FILE_TYPES=pdf,docx,pptx,jpg,jpeg,png,mp4

# Currency
CURRENCY=INR
WITHDRAWAL_MIN=500
PLATFORM_FEE=2

# Ideas
SUBMISSION_COST=2
SIGNUP_BONUS=6

# Security
SESSION_LIFETIME=7200
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=3600
```

## Step 6: Configure Domain (Optional)

1. Go to Settings > Domains in Railway
2. Add your custom domain (e.g., ideaone.com)
3. Update DNS records as instructed
4. Update `APP_URL` environment variable

## Step 7: Verify Deployment

1. Check the deployment logs in Railway
2. Visit your Railway URL
3. Test registration and login
4. Test idea submission
5. Test coin purchase (use test mode for Razorpay)
6. Test file upload

## Step 8: Set Up Monitoring

1. Enable Railway metrics
2. Set up error logging
3. Configure alerts for downtime
4. Monitor database usage in Supabase

## Important Notes

### Security
- NEVER commit `.env` file
- Always use strong JWT secret in production
- Enable HTTPS (automatic on Railway)
- Keep dependencies updated

### Database
- Supabase handles backups automatically
- Monitor database size in Supabase
- Set up RLS policies (already included in schema)

### Storage
- File size limits are enforced
- Delete old files periodically
- Monitor storage usage

### Scaling
- Railway automatically scales based on traffic
- Consider upgrading plan for production
- Set up CDN for static assets

## Troubleshooting

### Deployment Fails
- Check logs in Railway
- Verify all environment variables are set
- Ensure PHP version compatibility (PHP 8.0+)

### Database Connection Issues
- Verify Supabase credentials
- Check if database is paused in Supabase
- Test connection string locally

### File Upload Issues
- Verify Supabase storage buckets exist
- Check file size limits
- Ensure proper permissions

### Payment Issues
- Verify Razorpay keys
- Check webhook configuration
- Use test mode initially

## Cost Estimation

- Railway: $5/month (starter) or $20/month (pro)
- Supabase: Free tier up to 500MB database
- Razorpay: Pay per transaction (~2%)
- Domain: ~$10/year (optional)

## Post-Deployment Checklist

- [ ] Update APP_URL to production domain
- [ ] Test all user flows
- [ ] Verify email notifications work
- [ ] Test payment flow with Razorpay test mode
- [ ] Set up database backups
- [ ] Configure monitoring and alerts
- [ ] Update documentation
- [ ] Create admin user
- [ ] Set up custom domain (optional)
- [ ] Enable SSL certificate (automatic on Railway)

## Support

- Railway: https://docs.railway.app
- Supabase: https://supabase.com/docs
- Razorpay: https://razorpay.com/docs
- PHP: https://www.php.net/docs.php