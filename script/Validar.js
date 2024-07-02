function Validar() {
    var nomInput = document.getElementById('nom');
    var apeInput = document.getElementById('ape');
    var emailInput = document.getElementById('email');
    var telInput = document.getElementById('tel');
    var textarea = document.getElementById('textarea');
    var divError = document.getElementById('error');
    
    var isValid = true;
    divError.innerHTML = '';
  
    // Validación del nombre
    if (nomInput.value.trim() === '' || nomInput.value.length < 3) {
      divError.innerHTML += 'Debe ingresar un nombre válido (mínimo 3 letras)<br>';
      isValid = false;
    }
  
    // Validación del apellido
    if (apeInput.value.trim() === '' || apeInput.value.length < 3) {
      divError.innerHTML += 'Debe ingresar un apellido válido (mínimo 3 letras)<br>';
      isValid = false;
    }
  
    // Validación del teléfono (debe ser numérico y tener al menos 10 dígitos)
    var telefono = telInput.value.trim();
    if (!/^\d{10,}$/.test(telefono)) {
      divError.innerHTML += 'Debe ingresar un teléfono válido (debe contener al menos 10 dígitos numéricos)<br>';
      isValid = false;
    }
  
    // Validación del correo electrónico
    var email = emailInput.value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
      divError.innerHTML += 'Debe ingresar un correo electrónico válido<br>';
      isValid = false;
    }
  
    // Validación del textarea (mensaje)
    if (textarea.value.trim() === '') {
      divError.innerHTML += 'Debe ingresar un mensaje<br>';
      isValid = false;
    }
  
    return isValid;
  }
  
