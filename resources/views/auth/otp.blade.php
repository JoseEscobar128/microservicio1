<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verificación | MesaFacil</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <style>
    :root {
      --main-color: #D78D16;
      --main-color-light: #fcefdc;
    }

    .otp-input {
      width: 3.5rem;
      height: 3.5rem;
      font-size: 1.5rem;
      text-align: center;
      margin: 0 0.25rem;
      border: 2px solid #d1d5db;
      border-radius: 0.5rem;
      transition: all 0.3s;
    }

    .otp-input:focus {
      outline: none;
      border-color: var(--main-color);
      box-shadow: 0 0 0 3px rgba(215, 141, 22, 0.3);
    }

    .otp-input.filled {
      border-color: #10b981;
      background-color: #f0fdf4;
    }

    .otp-container {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin: 1.5rem 0;
    }
  </style>
</head>

<body class="bg-white min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white rounded-xl shadow-lg overflow-hidden animate__animated animate__fadeIn">

    <!-- Logo -->
    <div class="text-center mt-6">
      <img src="{{ asset('images/6.png') }}" alt="Logo Mesa Fácil" class="h-40 mx-auto mb-4">
    </div>

    <!-- Encabezado -->
    <div class="bg-[var(--main-color)] py-6 px-8 text-center">
      <div class="flex justify-center mb-3">
        <div class="bg-white/20 p-3 rounded-full">
          <i class="fas fa-shield-alt text-white text-2xl"></i>
        </div>
      </div>
      <h1 class="text-2xl font-bold text-white">Verificación de Seguridad</h1>
      <p class="text-white/80 mt-2">Hemos enviado un código a tu correo</p>
    </div>

    <!-- Cuerpo del formulario -->
    <div class="p-8">

      @if($errors->any())
      <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
        <div class="flex items-center">
          <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
          <h3 class="text-red-800 font-medium">Error de verificación</h3>
        </div>
        <ul class="mt-2 text-red-600 text-sm">
          @foreach($errors->all() as $error)
          <li class="flex items-start mt-1">
            <i class="fas fa-chevron-right text-red-400 text-xs mt-1 mr-2"></i>
            <span>{{ $error }}</span>
          </li>
          @endforeach
        </ul>
      </div>
      @endif

      <form method="POST" action="{{ route('cliente.otp.verify.form') }}" class="space-y-6" id="otpForm">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="client_id" value="{{ $client_id }}">
        <input type="hidden" name="redirect_uri" value="{{ $redirect_uri }}">
        <input type="hidden" name="state" value="{{ $state }}">
        <input type="hidden" name="otp" id="fullOtp">
        <input type="hidden" name="tipo_usuario" value="{{ session('tipo_usuario') }}">

        <div class="text-center">
          <p class="text-gray-700 mb-6">Por favor ingresa el código de 4 dígitos que enviamos a:<br>
            <span class="font-semibold text-[var(--main-color)]">{{ $email }}</span>
          </p>

          <div class="otp-container">
            <input type="text" maxlength="1" class="otp-input" data-index="1" autofocus inputmode="numeric">
            <input type="text" maxlength="1" class="otp-input" data-index="2" inputmode="numeric">
            <input type="text" maxlength="1" class="otp-input" data-index="3" inputmode="numeric">
            <input type="text" maxlength="1" class="otp-input" data-index="4" inputmode="numeric">
          </div>

          <button type="submit" id="submitBtn" disabled
            class="w-full py-3 px-4 bg-[var(--main-color)] hover:bg-opacity-90 text-white font-medium rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 disabled:opacity-50 disabled:transform-none disabled:cursor-not-allowed">
            <i class="fas fa-check-circle mr-2"></i> Verificar Código
          </button>
        </div>
      </form>

      <div class="mt-8 pt-6 border-t border-gray-200 text-center">
        <p class="text-sm text-gray-600 mb-2">¿No recibiste el código?</p>

        <form method="POST" action="{{ route('cliente.resendOtp') }}">
          @csrf
          <input type="hidden" name="email" value="{{ $email }}">
          <button type="submit" class="text-[var(--main-color)] hover:underline font-medium text-sm">
            <i class="fas fa-redo mr-1"></i> Reenviar código
          </button>
        </form>

        <p class="text-xs text-gray-500 mt-4">
          <i class="fas fa-lock mr-1"></i> Tu información está protegida con encriptación SSL
        </p>
      </div>
    </div>
  </div>

  <!-- Script OTP -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const otpInputs = document.querySelectorAll('.otp-input');
      const fullOtpInput = document.getElementById('fullOtp');
      const submitBtn = document.getElementById('submitBtn');
      const form = document.getElementById('otpForm');

      // Solo permitir números
      otpInputs.forEach(input => {
        input.addEventListener('input', (e) => {
          input.value = input.value.replace(/[^0-9]/g, ''); // bloquear letras
        });
      });

      otpInputs.forEach((input, index) => {
        input.addEventListener('input', function() {
          if (this.value.length === 1) {
            this.classList.add('filled');
            if (index < otpInputs.length - 1) {
              otpInputs[index + 1].focus();
            }
          } else if (this.value.length === 0) {
            this.classList.remove('filled');
            if (index > 0) {
              otpInputs[index - 1].focus();
            }
          }
          checkOTP();
        });

        input.addEventListener('keydown', function(e) {
          if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
            otpInputs[index - 1].focus();
          }
        });
      });

      function checkOTP() {
        let otp = '';
        let allFilled = true;

        otpInputs.forEach(input => {
          otp += input.value;
          if (input.value.length === 0) {
            allFilled = false;
          }
        });

        fullOtpInput.value = otp;
        submitBtn.disabled = !allFilled;
      }

      form.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');

        otpInputs.forEach((input, index) => {
          if (index < pasteData.length) {
            input.value = pasteData[index];
            input.classList.add('filled');
          } else {
            input.value = '';
            input.classList.remove('filled');
          }
        });

        if (pasteData.length >= otpInputs.length) {
          otpInputs[otpInputs.length - 1].focus();
        } else if (pasteData.length > 0) {
          otpInputs[pasteData.length].focus();
        }

        checkOTP();
      });
    });
  </script>
</body>

</html>