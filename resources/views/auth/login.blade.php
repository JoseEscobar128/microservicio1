<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Mesa Facil</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    :root {
      --main-color: #D78D16;
    }
  </style>
</head>

<body class="bg-white min-h-screen flex items-center justify-center px-4 py-8">
  <div class="w-full max-w-md bg-white rounded-lg shadow-lg overflow-hidden">
    
    <div class="text-center mt-6 mb-4">
  <img src="{{ asset('images/6.png') }}" alt="Logo Mesa Fácil" class="h-40 mx-auto mb-2">
  <h1 class="text-2xl font-bold text-[color:var(--main-color)]">Iniciar sesión</h1>
</div>


    <!-- Formulario -->
    <div class="p-6">

      {{-- Mensajes de sesión --}}
      @if(session('error'))
      <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700">
        <p>{{ session('error') }}</p>
      </div>
      @endif

      @if(session('success'))
      <div class="mb-4 p-3 bg-green-100 border-l-4 border-green-500 text-green-700">
        <p>{{ session('success') }}</p>
      </div>
      @endif

      {{-- Errores de validación --}}
      @if($errors->any())
      <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700">
        <ul class="text-sm pl-4 list-disc">
          @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
      @endif

      <form method="POST" action="{{ route('cliente.login.form') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="client_id" value="{{ request('client_id') }}">
        <input type="hidden" name="redirect_uri" value="{{ request('redirect_uri') }}">
        <input type="hidden" name="state" value="{{ request('state') }}">

        {{-- Correo o Usuario --}}
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
            Correo o Usuario
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-envelope text-gray-400"></i>
            </div>
            <input type="text" id="login" name="login" value="{{ old('email') }}"
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[color:var(--main-color)] focus:border-[color:var(--main-color)]"
              placeholder="correo@ejemplo.com o usuario123" required autofocus>
          </div>
        </div>

        {{-- Contraseña --}}
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            Contraseña
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-lock text-gray-400"></i>
            </div>
            <input type="password" id="password" name="password"
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[color:var(--main-color)] focus:border-[color:var(--main-color)]"
              placeholder="••••••••" required>
          </div>
        </div>

        {{-- Botón --}}
        <div>
          <button type="submit"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[color:var(--main-color)] hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[color:var(--main-color)]">
            <i class=""></i> Iniciar sesión
          </button>
        </div>
      </form>
    </div>
  </div>
</body>

</html>
