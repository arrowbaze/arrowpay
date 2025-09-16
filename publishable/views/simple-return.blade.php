{{-- resources/views/vendor/arrowbaze/simple-return.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Status - ArrowBaze</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Tailwind CDN for quick styling --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white shadow-lg rounded-xl p-8 max-w-lg w-full text-center">
        @if(session('arrowbaze_payment_status') === 'success')
            <div class="text-green-500 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" 
                     class="w-16 h-16 mx-auto" 
                     fill="none" viewBox="0 0 24 24" 
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0a9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Payment Successful</h1>
            <p class="text-gray-600 mt-2">
                Thank you! Your transaction has been completed successfully.
            </p>
        @else
            <div class="text-red-500 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" 
                     class="w-16 h-16 mx-auto" 
                     fill="none" viewBox="0 0 24 24" 
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 9v2m0 4h.01M12 5a7 7 0 100 14a7 7 0 000-14z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Payment Failed</h1>
            <p class="text-gray-600 mt-2">
                Unfortunately, your payment could not be processed.  
                Please try again or contact support if the issue persists.
            </p>
        @endif

        <a href="{{ url('/') }}" 
           class="mt-6 inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition">
            Return to Home
        </a>
    </div>

</body>
</html>
