require('dotenv').config();
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
mongoose.connect(process.env.MONGODB_URI);

// Firebase Storage Setup
const storage = new Storage({
  projectId: process.env.FIREBASE_PROJECT_ID,
  keyFilename: process.env.GOOGLE_APPLICATION_CREDENTIALS
});
const bucket = storage.bucket(process.env.FIREBASE_BUCKET_NAME);

// Image Upload Middleware
const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 5 * 1024 * 1024 } // 5MB
});

// Routes
app.post('/api/register', async (req, res) => {
  try {
    const hashedPassword = await bcrypt.hash(req.body.password, 10);
    const user = new User({ email: req.body.email, password: hashedPassword });
    await user.save();
    res.status(201).send();
  } catch (err) {
    res.status(500).send(err.message);
  }
});

app.post('/api/login', async (req, res) => {
  const user = await User.findOne({ email: req.body.email });
  if (!user) return res.status(400).send('User not found');
  
  if (await bcrypt.compare(req.body.password, user.password)) {
    const token = jwt.sign({ _id: user._id }, process.env.JWT_SECRET);
    res.send({ token });
  } else {
    res.status(400).send('Invalid credentials');
  }
});

app.post('/api/images', upload.single('image'), async (req, res) => {
  if (!req.file) return res.status(400).send('No file uploaded');
  
  const blob = bucket.file(`images/${Date.now()}_${req.file.originalname}`);
  const blobStream = blob.createWriteStream();
  
  blobStream.on('error', err => res.status(500).send(err));
  blobStream.on('finish', () => {
    const publicUrl = `https://storage.googleapis.com/${bucket.name}/${blob.name}`;
    res.status(201).send({ imageUrl: publicUrl });
  });
  
  blobStream.end(req.file.buffer);
});

app.listen(process.env.PORT, () => 
  console.log(`Server running on port ${process.env.PORT}`)
);