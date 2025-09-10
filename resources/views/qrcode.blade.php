@php
    use Illuminate\Support\Facades\Storage;
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .qrcode-image {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    @if(!empty($order->qrcode_path))
        <img src="{{ Storage::disk('pix')->url($order->qrcode_path) }}" alt="QR Code" class="qrcode-image">
    @else
        <p>QR Code não disponível</p>
    @endif
</body>
</html>
