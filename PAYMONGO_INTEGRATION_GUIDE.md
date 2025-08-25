# PayMongo Test API Integration Guide

## 🚀 **Complete PayMongo Integration for NeoCafe**

This guide will help you set up and test the PayMongo payment integration for both regular checkout and Available Today checkout.

---

## 📋 **Setup Steps**

### **1. Get PayMongo Test API Keys**

1. **Sign up** at [PayMongo Dashboard](https://dashboard.paymongo.com/)
2. **Navigate** to API Keys section
3. **Copy** your test keys:
   - **Secret Key**: `sk_test_xxxxxxxxxx`
   - **Public Key**: `pk_test_xxxxxxxxxx`

### **2. Configure API Keys**

Open `frontend/pages/cart/paymongo-config.php` and update:

```php
// Replace with your actual test keys
define('PAYMONGO_SECRET_KEY', 'sk_test_your_actual_secret_key_here');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_your_actual_public_key_here');
```

### **3. Database Updates**

Add these columns to your `orders` table:

```sql
ALTER TABLE orders ADD COLUMN payment_id VARCHAR(255) NULL;
ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN amount_paid DECIMAL(10,2) NULL;
ALTER TABLE orders ADD COLUMN paid_at TIMESTAMP NULL;
```

---

## 💳 **Payment Methods Supported**

### **1. GCash** 
- **Type**: E-wallet redirect
- **User Experience**: Redirects to GCash app/website
- **Test**: Use test GCash account

### **2. Maya (PayMaya)**
- **Type**: E-wallet redirect  
- **User Experience**: Redirects to Maya app/website
- **Test**: Use test Maya account

### **3. Credit/Debit Cards**
- **Type**: Direct card payment
- **User Experience**: Card form on checkout page
- **Test**: Use test card numbers below

---

## 🧪 **Test Card Numbers**

### **Success Cards:**
```
Visa: 4343434343434345
Mastercard: 5555555555554444
```

### **Decline Cards:**
```
Visa Decline: 4571736000000075
Mastercard Decline: 5506900490000436
```

### **Card Details for Testing:**
- **Expiry**: Any future date (e.g., 12/25)
- **CVC**: Any 3-4 digits (e.g., 123)
- **Name**: Any name

---

## 🔄 **Payment Flow**

### **Available Today Checkout:**
1. **User** adds items to Available Today cart
2. **User** clicks checkout → `availtoday-checkout.php`
3. **User** fills form and selects payment method
4. **System** creates temporary order
5. **PayMongo** processes payment
6. **User** redirected to success/failure page
7. **System** finalizes order and clears cart

### **Regular Checkout:**
1. **User** adds items to regular cart
2. **User** clicks checkout → `checkout.php` (to be integrated)
3. **Same flow** as Available Today

---

## 📁 **Files Created/Modified**

### **New Files:**
- `paymongo-config.php` - PayMongo configuration and API class
- `process-payment.php` - Payment processing handler
- `payment-return.php` - Payment success/failure handler
- `payment-success.php` - Payment success page
- `payment-failed.php` - Payment failure page

### **Modified Files:**
- `availtoday-checkout.php` - Added PayMongo integration
- `availtoday-cart.js` - Updated checkout redirect

---

## 🧪 **Testing Guide**

### **Test Scenario 1: GCash Payment**
1. Add items to Available Today cart
2. Go to checkout
3. Select **GCash** payment
4. Fill required fields
5. Click **Place Order**
6. Should redirect to PayMongo GCash test page
7. Complete test payment
8. Should return to success page

### **Test Scenario 2: Card Payment**
1. Add items to Available Today cart
2. Go to checkout  
3. Select **Credit/Debit Card**
4. Enter test card: `4343434343434345`
5. Enter expiry: `12/25`, CVC: `123`
6. Click **Place Order**
7. Should process payment and show success

### **Test Scenario 3: Failed Payment**
1. Use decline card: `4571736000000075`
2. Should show failure page with error message

---

## 🚨 **Troubleshooting**

### **Common Issues:**

#### **1. "Invalid API Key" Error**
- ✅ Check API keys in `paymongo-config.php`
- ✅ Ensure using test keys (start with `sk_test_` and `pk_test_`)

#### **2. "Database Error" 
- ✅ Run the database migration SQL
- ✅ Check database connection

#### **3. "Payment Failed" Always**
- ✅ Verify test card numbers
- ✅ Check PayMongo dashboard for logs

#### **4. Redirect Not Working**
- ✅ Check return URLs in `paymongo-config.php`
- ✅ Ensure proper domain configuration

### **Debug Mode:**
Check browser console and PHP error logs for detailed error messages.

---

## 🔧 **Webhook Setup (Optional)**

For production, set up webhooks:

1. **PayMongo Dashboard** → Webhooks
2. **Add endpoint**: `https://yourdomain.com/frontend/pages/cart/webhook.php`
3. **Select events**: `payment.paid`, `payment.failed`

---

## 🎯 **Next Steps**

### **For Production:**
1. **Replace** test API keys with live keys
2. **Update** domain configurations
3. **Set up** webhooks for real-time updates
4. **Add** proper error handling and logging
5. **Test** with real payment methods

### **Additional Features:**
1. **Refunds** - Add refund functionality
2. **Installments** - Add installment options
3. **Multi-currency** - Support multiple currencies
4. **Receipts** - Generate payment receipts

---

## 📞 **Support**

### **PayMongo Documentation:**
- [PayMongo Docs](https://developers.paymongo.com/)
- [Test Cards](https://developers.paymongo.com/docs/testing-cards)

### **Integration Issues:**
- Check PayMongo dashboard logs
- Review browser console errors
- Check PHP error logs

---

## ✅ **Integration Complete!**

Your PayMongo integration is now ready for testing! 

**Available Today checkout** now supports:
- ✅ **GCash payments**
- ✅ **Maya payments** 
- ✅ **Card payments**
- ✅ **Auto-assigned delivery methods**
- ✅ **No calendar/time selection** (as requested)
- ✅ **Success/failure handling**
- ✅ **Order completion**

**Test it out** and let me know if you need any adjustments! 🎉
