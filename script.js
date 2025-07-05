// Updated Login System
loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  try {
    const response = await fetch('/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: document.getElementById('email').value,
        password: document.getElementById('password').value
      })
    });
    
    if (response.ok) {
      const data = await response.json();
      localStorage.setItem('token', data.token);
      loginScreen.classList.add('hidden');
      mainContent.classList.remove('hidden');
      loadImages();
    } else {
      alert('Login failed');
    }
  } catch (err) {
    console.error('Login error:', err);
  }
});

// Image Upload Function
async function uploadImage(file) {
  const formData = new FormData();
  formData.append('image', file);
  
  try {
    const response = await fetch('/api/images', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: formData
    });
    
    if (response.ok) {
      const data = await response.json();
      return data.imageUrl;
    }
  } catch (err) {
    console.error('Upload error:', err);
  }
}

// Updated Add Image Button
addImageBtn.addEventListener('click', async () => {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*';
  
  input.onchange = async (e) => {
    const file = e.target.files[0];
    if (file) {
      const imageUrl = await uploadImage(file);
      if (imageUrl) {
        const newImage = document.createElement('img');
        newImage.src = imageUrl;
        imageContainer.appendChild(newImage);
        localStorage.setItem('lastPostDate', new Date().toISOString());
      }
    }
  };
  
  input.click();
});

// Load User's Images
async function loadImages() {
  try {
    const response = await fetch('/api/user/images', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });
    
    if (response.ok) {
      const images = await response.json();
      imageContainer.innerHTML = '';
      images.forEach(url => {
        const img = document.createElement('img');
        img.src = url;
        imageContainer.appendChild(img);
      });
    }
  } catch (err) {
    console.error('Error loading images:', err);
  }
}