# PayMongo Live/Production Setup Instructions

## 🔴 IMPORTANT: You're Now in LIVE MODE

Your system is now configured to process **REAL payments with REAL money**. Follow these steps carefully.

---

## Step 1: Get Your Live API Keys

### 1. Go to PayMongo Dashboard

Visit: https://dashboard.paymongo.com/

### 2. Login to Your Account

- Use your PayMongo business account
- If you don't have one, sign up at https://paymongo.com/

### 3. Verify Your Business

**CRITICAL**: PayMongo requires business verification before you can use live keys:

- Submit business documents
- Provide business registration
- Complete KYC (Know Your Customer) requirements
- Wait for approval (usually 1-3 business days)

### 4. Get Live API Keys

Once verified:

1. Go to **Developers** → **API Keys** in the dashboard
2. You'll see two sets of keys:
   - **Test Keys** (sk*test*_ and pk*test*_)
   - **Live Keys** (sk*live*_ and pk*live*_) ← You need these!
3. Copy your **Live Secret Key** (starts with `sk_live_`)
4. Copy your **Live Public Key** (starts with `pk_live_`)

---

## Step 2: Update Your Configuration

### Open this file:

```
d:\XAMPP\htdocs\NeoCafe\frontend\pages\cart\paymongo-config.php
```

### Replace these lines (around line 42-43):

```php
$this->secret_key = 'sk_live_your_live_secret_key_here'; // ⚠️ REPLACE
$this->public_key = 'pk_live_your_live_public_key_here'; // ⚠️ REPLACE
```

### With your actual keys:

```php
$this->secret_key = 'sk_live_ABC123XYZ...'; // Your actual live secret key
$this->public_key = 'pk_live_DEF456UVW...'; // Your actual live public key
```

---

## Step 3: Security Best Practices

### ⚠️ NEVER commit API keys to Git!

Add to your `.gitignore`:

```
paymongo-config.php
.env
```

### 🔒 Better Approach - Use Environment Variables

1. Install `vlucas/phpdotenv`:

   ```bash
   composer require vlucas/phpdotenv
   ```

2. Create `.env` file:

   ```
   PAYMONGO_SECRET_KEY=sk_live_your_actual_key
   PAYMONGO_PUBLIC_KEY=pk_live_your_actual_key
   ```

3. Update `paymongo-config.php`:

   ```php
   require_once __DIR__ . '/../../../vendor/autoload.php';
   $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
   $dotenv->load();

   $this->secret_key = $_ENV['PAYMONGO_SECRET_KEY'];
   $this->public_key = $_ENV['PAYMONGO_PUBLIC_KEY'];
   ```

---

## Step 4: Test in Live Mode

### Supported Real Cards

Once you have live keys, customers can use:

- ✅ Visa (any valid Visa card)
- ✅ Mastercard (any valid Mastercard)
- ✅ JCB (any valid JCB card)

### Test Your Integration

1. Make a small real payment (e.g., ₱50)
2. Use your own card
3. Verify the transaction appears in PayMongo Dashboard
4. Check your bank statement

---

## Step 5: Production Checklist

Before going live, ensure:

- [ ] PayMongo business account is verified
- [ ] Live API keys are configured
- [ ] SSL certificate is installed on your domain
- [ ] Test transactions completed successfully
- [ ] Payment confirmation emails work
- [ ] Webhook endpoints are configured (optional but recommended)
- [ ] Error logging is enabled
- [ ] Customer support contact is available
- [ ] Terms & conditions include refund policy

---

## Alternative: Stay in Test Mode

If you're not ready for live mode yet, you can **switch back to test mode**:

### 1. Change paymongo-config.php (line 18):

```php
define('PAYMONGO_MODE', 'sandbox'); // Change 'live' to 'sandbox'
```

### 2. Use test keys (lines 42-43):

```php
$this->secret_key = 'sk_test_yb8pkZvUA3WjHP6T4FKhgudU';
$this->public_key = 'pk_test_1XUMJ3yMs8QZugdq3uWr8vYU';
```

### 3. Use PayMongo test cards:

- **Success**: `4343434343434345`
- **Decline**: `4571736000000075`
- Expiry: Any future date (e.g., `12/25`)
- CVC: Any 3 digits (e.g., `123`)

---

## Support

### PayMongo Support

- Email: support@paymongo.com
- Documentation: https://developers.paymongo.com/docs
- Discord: https://discord.gg/paymongo

### Common Issues

**Error: "Please use PayMongo test cards only"**

- You're using test keys with a real card
- Solution: Either use test cards OR switch to live keys

**Error: "Invalid API key"**

- Wrong key format or expired
- Solution: Copy fresh keys from dashboard

**Error: "Account not verified"**

- Business verification pending
- Solution: Complete KYC in PayMongo dashboard

---

## What Changed in Your Code?

1. **paymongo-config.php**: Switched from test to live mode
2. **card-payment.php**: Added live mode warning banner
3. **UI Updates**: Security badges, PCI compliance indicators
4. **Error Handling**: Better error messages for live transactions

---

## Need Help?

If you encounter issues:

1. Check PayMongo dashboard for transaction logs
2. Check PHP error logs: `d:\XAMPP\htdocs\NeoCafe\logs\payment_errors.log`
3. Enable detailed logging in paymongo-config.php
4. Contact PayMongo support for API issues

---

**Last Updated**: November 14, 2025
**Mode**: 🔴 LIVE/PRODUCTION
**Security**: SSL Required, PCI Compliant
