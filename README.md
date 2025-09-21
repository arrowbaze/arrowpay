[![Latest Version on Packagist](https://img.shields.io/packagist/v/arrowpay/arrowbaze.svg?style=flat-square)](https://packagist.org/packages/arrowpay/arrowbaze)
[![Total Downloads](https://img.shields.io/packagist/dt/arrowpay/arrowbaze.svg?style=flat-square)](https://packagist.org/packages/arrowpay/arrowbaze)

ArrowPay est un **package PHP** qui intègre **Orange Money Webpay** dans vos applications (Laravel, Symfony, etc.), offrant un moyen simple et sécurisé d’accepter des paiements.

---

## 🚀 Installation

Installer le package via Composer :

```bash
composer require arrowpay/arrowbaze
````

---

## ⚙️ Publication de la configuration

Après l’installation, publiez le fichier de configuration :

```bash
php artisan vendor:publish --provider="Arrowpay\ArrowBaze\ArrowBazeServiceProvider" --tag=config
```

Cela va créer :

```
config/arrowbaze.php
```

---

## 🔑 Configuration

Avant d’utiliser le package, demandez une **clé de licence** à **ArrowBAZE** via [arrowbaze.com/contact](https://arrowbaze.com/contact).

Mettez à jour votre fichier `.env` :

```dotenv
# Clé de licence (unique par partenaire/domaine)
ARROWPAY_LICENSE_KEY="VOTRE_CLE_LICENCE_ARROWBAZE"

# Identifiants Partenaires (fournis par Orange Money)
ARROWPAY_MERCHANT_KEY="VOTRE_MERCHANT_KEY"
ARROWPAY_CLIENT_ID="VOTRE_CLIENT_ID"
ARROWPAY_CLIENT_SECRET="VOTRE_CLIENT_SECRET"

# Routes (relatives à votre domaine)
ARROWPAY_RETURN_ROUTE="my-payments/return/{orderId}"
ARROWPAY_CANCEL_ROUTE="my-payments/cancel"
ARROWPAY_NOTIFY_ROUTE="my-payments/notify"

# Devise par défaut
ARROWPAY_CURRENCY="XOF"
```

---

## 🏷️ Alias Laravel

Ajoutez l’alias suivant dans `config/app.php` si ce n’est pas déjà fait :

```php
'aliases' => [
    // ...
    'ArrowPay' => Arrowpay\ArrowBaze\Facades\ArrowPay::class,
],
```

Cela vous permet d’appeler directement :

```php
ArrowPay::initializePayment([...]);
```



---

## 📦 Utilisation

### Initialiser un paiement

```php
use ArrowPay;

$payment = ArrowPay::initializePayment([
    'order_id'   => 'ORDER123',
    'amount'     => 5000,
    'return_url' => route('my.return', ['orderId' => 'ORDER123']),
    'cancel_url' => route('my.cancel'),
    'notif_url'  => route('my.notify'),
    'currency'   => 'XOF', // ou OUV en environnement test
    'reference'  => 'APP_NAME',
    'lang'       => 'fr',
]);

return redirect($payment['payment_url']);
```

---

### Vérifier le statut d’une transaction

```php
$status = ArrowPay::checkTransactionStatus('ORDER123');

if ($status['status'] === 'SUCCESS') {
    // Marquer la commande comme payée
}
```

---

## ⚡ Routes

Vous avez deux choix :

### 1. **Définir vos propres routes**

Exemple de route personnaliser (`routes/web.php`) :

```php
use App\Http\Controllers\PaymentController;

Route::prefix('my-payments')->group(function () {
    Route::post('pay', [PaymentController::class, 'pay'])->name('my.pay');
    Route::get('return/{orderId}', [PaymentController::class, 'handleReturn'])->name('my.return');
    Route::get('cancel', [PaymentController::class, 'handleCancel'])->name('my.cancel');
    Route::post('notify', [PaymentController::class, 'handleNotify'])->name('my.notify');
});
```

### 2. **Utiliser les routes par défaut fournies par le package**

Si vous ne définissez rien, le package enregistre automatiquement :

* `arrowpay/return/{orderId}`
* `arrowpay/cancel`
* `arrowpay/notify`

---

````markdown
## ⚡ Démo Rapide (Quickstart Demo)

Une **intégration prête à l’emploi** pour tester en quelques minutes.

---

### 1. Créer le Modèle `Payment` et sa Migration

```bash
php artisan make:model Payment -m
````

Dans la migration :

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->string('order_id')->unique();
    $table->string('status')->default('pending');
    $table->json('payload')->nullable();
    $table->string('payment_url')->nullable();
    $table->string('pay_token')->nullable();
    $table->timestamps();
});
```

Exécuter :

```bash
php artisan migrate
```

---

### 2. Ajouter les Routes (`routes/web.php` ou `routes/api.php`)

```php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('my-payments')->group(function () {
    Route::post('pay', [PaymentController::class, 'pay'])->name('my.pay');
    Route::get('return/{orderId}', [PaymentController::class, 'handleReturn'])->name('my.return');
    Route::get('cancel', [PaymentController::class, 'handleCancel'])->name('my.cancel');
    Route::post('notify', [PaymentController::class, 'handleNotify'])->name('my.notify');
});
```

---

### 3. Créer le `PaymentController`

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use ArrowPay;

class PaymentController extends Controller
{
    public function pay(Request $request)
    {
        $orderId = 'ABZ_' . uniqid();

        $payload = [
            'amount'      => $request->amount ?? 100,
            'currency'    => config('arrowbaze.currency', 'XOF'),
            'reference'   => $request->reference ?? 'ArrowPay Démo',
            'return_url'  => route('my.return', ['orderId' => $orderId]),
            'cancel_url'  => route('my.cancel'),
            'notify_url'  => route('my.notify'),
            'order_id'    => $orderId,
            'lang'        => 'fr'
        ];

        $response = ArrowPay::initializePayment($payload);

        Payment::create([
            'order_id'    => $orderId,
            'status'      => 'pending',
            'payload'     => $payload,
            'payment_url' => $response['payment_url'] ?? null,
            'pay_token'   => $response['pay_token'] ?? null,
        ]);

        return redirect($response['payment_url']);
    }

    public function handleReturn($orderId)
    {
        $payment = Payment::where('order_id', $orderId)->firstOrFail();

        return view('payments.return', compact('payment'));
    }

    public function handleCancel()
    {
        return view('payments.cancel');
    }

    public function handleNotify(Request $request)
    {
        $payment = Payment::where('order_id', $request->txnid)->first();

        if ($payment) {
            $payment->update([
                'status' => $request->status == 201 ? 'success' : 'failed',
            ]);
        }

        return response()->json(['message' => 'Notification reçue']);
    }
}
```

---

### 4. Créer les Vues Démo

**resources/views/payments/return.blade.php**

```blade
<h1>Retour de Paiement</h1>
<p>Numéro de commande : {{ $payment->order_id }}</p>
<p>Statut : {{ $payment->status }}</p>
```

**resources/views/payments/cancel.blade.php**

```blade
<h1>Paiement Annulé</h1>
<p>Votre paiement a été annulé.</p>
```

---

### 5. Tester 

* Accédez à `/my-payments/pay`
* Vous serez redirigé vers **Orange Money Webpay** (sandbox)
* Effectuez ou annulez un paiement
* Les notifications mettront automatiquement à jour votre base de données

---

## 📜 Licence

MIT — voir [LICENSE](LICENSE).

---

## 📧 Support

Pour toute aide à l’intégration, contactez **ArrowPay Support** :
📩 [hello@arrowbaze.com](mailto:hello@arrowbaze.com)
🌍 [Site Web](https://arrowbaze.com/contact)

```