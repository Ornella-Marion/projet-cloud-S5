#!/bin/bash
echo "🚀 Démarrage des Firebase Emulators..."
firebase emulators:start --only auth --project demo-project &
EMULATOR_PID=$!
echo "Emulator démarré avec PID: $EMULATOR_PID"

# Attendre que l'emulator soit prêt
sleep 5

# Tester la connexion
if curl -s http://127.0.0.1:9099 > /dev/null; then
    echo "✅ Emulator accessible sur http://127.0.0.1:9099"
else
    echo "❌ Emulator non accessible"
fi

echo "📝 Appuyez sur Ctrl+C pour arrêter les emulators"
wait $EMULATOR_PID
