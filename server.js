const express = require('express');
const mongoose = require('mongoose');
const multer = require('multer');
const { Storage } = require('@google-cloud/storage');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

// MongoDB Connection
mongoose.connect('mongodb+srv://you:your-password@cluster0.mongodb.net/fitness-app?retryWrites=true&w=majority');

// User Schema
const userSchema = new mongoose.Schema({
  username: String,
  password: String
});
const User = mongoose.model('User', userSchema);

// Firebase Storage Setup
const storage = new Storage({
  projectId: 'your-project-id',
  keyFilename: 'service-account.json'
});
const bucket = storage.bucket('your-bucket-name');

// Image Upload Middleware
const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 5 * 1024 * 1024 } // 5MB limit
});

// Routes
app.post('/api/register', async (req, res) => {
  try {
    const hashedPassword = await bcrypt.hash(req.body.password, 10);
    const user = new User({
      username: req.body.username,
      password: hashedPassword
    });
    await user.save();
    res.status(201).send();
  } catch {
    res.status(500).send();
  }
});

app.post('/api/login', async (req, res) => {
  const user = await User.findOne({ username: req.body.username });
  if (!user) return res.status(400).send('User not found');
  
  if (await bcrypt.compare(req.body.password, user.password)) {
    const token = jwt.sign({ _id: user._id }, 'your-secret-key');
    res.send({ token });
  } else {
    res.status(400).send('Invalid credentials');
  }
});

app.post('/api/images', upload.single('image'), async (req, res) => {
  if (!req.file) return res.status(400).send('No file uploaded');
  
  const blob = bucket.file(`images/${Date.now()}_${req.file.originalname}`);
  const blobStream = blob.createWriteStream();
  
  blobStream.on('error', err => {
    res.status(500).send(err);
  });
  
  blobStream.on('finish', () => {
    const publicUrl = `https://storage.googleapis.com/${bucket.name}/${blob.name}`;
    res.status(201).send({ imageUrl: publicUrl });
  });
  
  blobStream.end(req.file.buffer);
});

app.listen(3000, () => console.log('Server running on port 3000'));