# PayMongo Sandbox Mode Configuration

## ✅ Current Status: SANDBOX/TEST MODE

All PayMongo integrations in this system are configured for **SANDBOX/TEST MODE ONLY**.

## 🔧 Configuration File

**File:** `frontend/pages/cart/paymongo-config.php`

### Current Settings:
```php
define('PAYMONGO_MODE', 'sandbox'); // SANDBOX MODE
$this->secret_key = 'sk_test_yb8pkZvUA3WjHP6T4FKhgudU'; // TEST KEY
$this->public_key = 'pk_test_1XUMJ3yMs8QZugdq3uWr8vYU'; // TEST KEY
```

## 🧪 Sandbox Mode Features

### What Works in Sandbox:
- ✅ API integration testing
- ✅ Payment flow testing
- ✅ Order creation testing
- ✅ Test card numbers
- ✅ Webhook testing
- ✅ Error handling testing

### What Doesn't Work in Sandbox:
- ❌ Real money transactions
- ❌ Real GCash/Maya payments
- ❌ Real bank transfers
- ❌ Production payment gateway
- ❌ Live customer payments

## 🎯 Test Card Numbers

For testing card payments in sandbox mode:

```
Visa Success:       4343434343434345
Visa Decline:       4571736000000075
Mastercard Success: 5555555555554444
Mastercard Decline: 5506900490000436
```

## 📋 Files Using PayMongo

1. **`paymongo-config.php`** - Configuration and API class
2. **`process-payment.php`** - Payment processing
3. **`payment-return.php`** - Payment callback handler
4. **`checkout.php`** - Checkout form with PayMongo integration
5. **`debug-payment-flow.php`** - Debug tool for testing

## 🔄 Switching to Production Mode

When ready to accept real payments:

### Step 1: Get Production Keys
1. Apply for PayMongo production access
2. Complete business verification
3. Get approved by PayMongo
4. Obtain production keys (sk_live_* and pk_live_*)

### Step 2: Update Configuration
Edit `paymongo-config.php`:

```php
// Change from:
define('PAYMONGO_MODE', 'sandbox');
$this->secret_key = 'sk_test_...';
$this->public_key = 'pk_test_...';

// To:
define('PAYMONGO_MODE', 'live');
$this->secret_key = 'sk_live_YOUR_LIVE_SECRET_KEY';
$this->public_key = 'pk_live_YOUR_LIVE_PUBLIC_KEY';
```

### Step 3: Update Return URLs
Ensure return URLs point to your production domain with HTTPS:
```php
https://yourdomain.com/frontend/pages/cart/payment-return.php
```

### Step 4: Test in Production
1. Test with small amounts first
2. Verify webhooks work
3. Test all payment methods
4. Monitor logs for errors

## ⚠️ Important Notes

### Security:
- ✅ Never commit live keys to version control
- ✅ Use environment variables for production keys
- ✅ Keep test and live keys separate
- ✅ Rotate keys if compromised

### Testing:
- ✅ Always test in sandbox first
- ✅ Use debug tool for troubleshooting
- ✅ Check logs for errors
- ✅ Verify order creation works

### Deployment:
- ✅ Use HTTPS in production
- ✅ Verify webhook endpoints
- ✅ Test payment flow end-to-end
- ✅ Monitor transaction logs

## 🔍 How to Verify Mode

### Check Configuration:
```php
// In any PHP file that includes paymongo-config.php
if (isPayMongoSandboxMode()) {
    echo "Running in SANDBOX mode";
} else {
    echo "Running in LIVE mode";
}
```

### Check Logs:
Look for this in `logs/payment_errors.log`:
```
[PAYMONGO] Running in SANDBOX/TEST mode - No real transactions
[PAYMONGO-SANDBOX] Generating return URL for regular order
```

### Check Keys:
- Test keys start with: `sk_test_` or `pk_test_`
- Live keys start with: `sk_live_` or `pk_live_`

## 📞 Support

### PayMongo Documentation:
- API Docs: https://developers.paymongo.com/docs
- Test Mode: https://developers.paymongo.com/docs/testing
- Webhooks: https://developers.paymongo.com/docs/webhooks

### Internal Support:
- Use `debug-payment-flow.php` for testing
- Check `logs/payment_errors.log` for errors
- Review this document for configuration

## ✅ Verification Checklist

Before going live, verify:

- [ ] Production keys obtained from PayMongo
- [ ] Keys updated in `paymongo-config.php`
- [ ] PAYMONGO_MODE set to 'live'
- [ ] Return URLs use HTTPS
- [ ] Return URLs point to production domain
- [ ] Webhooks configured in PayMongo dashboard
- [ ] Test transactions completed successfully
- [ ] Error logging working
- [ ] Order creation working
- [ ] Email notifications working
- [ ] Inventory updates working

---

**Current Status:** 🧪 SANDBOX/TEST MODE - Safe for development and testing
**Last Updated:** November 6, 2025
