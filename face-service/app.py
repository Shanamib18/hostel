"""
LBSCEK Hostel Management - Face Recognition Service
Handles face registration and verification for mess attendance and entry/exit.
"""

import base64
import os
import uuid
import json
from io import BytesIO

from flask import Flask, request, jsonify
from flask_cors import CORS
import face_recognition
import numpy as np
from PIL import Image

app = Flask(__name__)
CORS(app)

# In-memory store for face encodings (in production, use Redis or DB)
ENCODINGS_DIR = os.path.join(os.path.dirname(__file__), 'encodings')
os.makedirs(ENCODINGS_DIR, exist_ok=True)


def get_encoding_path(encoding_id):
    return os.path.join(ENCODINGS_DIR, f"{encoding_id}.json")


def load_encodings_for_type(user_type):
    """Load all encodings for a user type from disk."""
    encodings = []
    if not os.path.exists(ENCODINGS_DIR):
        return encodings
    for f in os.listdir(ENCODINGS_DIR):
        if not f.endswith('.json'):
            continue
        path = os.path.join(ENCODINGS_DIR, f)
        try:
            with open(path, 'r') as fp:
                data = json.load(fp)
                if data.get('user_type') == user_type:
                    encodings.append(data)
        except Exception:
            pass
    return encodings


@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'service': 'face-recognition'})


@app.route('/register', methods=['POST'])
def register():
    """Register a new face encoding."""
    data = request.get_json()
    if not data or 'image' not in data or 'user_type' not in data or 'user_id' not in data:
        return jsonify({'error': 'Missing image, user_type or user_id'}), 400

    try:
        img_data = base64.b64decode(data['image'])
        img = Image.open(BytesIO(img_data)).convert('RGB')
        img_array = np.array(img)

        face_locs = face_recognition.face_locations(img_array)
        if not face_locs:
            return jsonify({'error': 'No face detected in image'}), 400
        if len(face_locs) > 1:
            return jsonify({'error': 'Multiple faces detected. Please use a single face image.'}), 400

        encodings = face_recognition.face_encodings(img_array, face_locs)
        if not encodings:
            return jsonify({'error': 'Could not encode face'}), 400

        encoding_id = str(uuid.uuid4())
        enc_data = {
            'encoding_id': encoding_id,
            'user_type': data['user_type'],
            'user_id': data['user_id'],
            'encoding': encodings[0].tolist()
        }
        path = get_encoding_path(encoding_id)
        with open(path, 'w') as fp:
            json.dump(enc_data, fp)

        return jsonify({'encoding_id': encoding_id, 'message': 'Face registered successfully'})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/verify', methods=['POST'])
def verify():
    """Verify a face against registered encodings and return user_id if match."""
    data = request.get_json()
    if not data or 'image' not in data or 'user_type' not in data:
        return jsonify({'error': 'Missing image or user_type'}), 400

    user_type = data['user_type']
    tolerance = float(data.get('tolerance', 0.5))

    try:
        img_data = base64.b64decode(data['image'])
        img = Image.open(BytesIO(img_data)).convert('RGB')
        img_array = np.array(img)

        face_locs = face_recognition.face_locations(img_array)
        if not face_locs:
            return jsonify({'error': 'No face detected'}), 400

        unknown_encodings = face_recognition.face_encodings(img_array, face_locs)
        if not unknown_encodings:
            return jsonify({'error': 'Could not encode face'}), 400

        registered = load_encodings_for_type(user_type)
        if not registered:
            return jsonify({'error': 'No faces registered'}), 404

        for rec in registered:
            known_enc = np.array(rec['encoding'])
            matches = face_recognition.compare_faces([known_enc], unknown_encodings[0], tolerance=tolerance)
            if any(matches):
                return jsonify({
                    'matched': True,
                    'user_id': rec['user_id'],
                    'encoding_id': rec['encoding_id']
                })

        return jsonify({'matched': False, 'error': 'Face not recognized'}), 404
    except Exception as e:
        return jsonify({'error': str(e)}), 500


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
