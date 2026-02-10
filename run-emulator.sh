#!/bin/bash
echo "🚀 Démarrage de Firebase Auth Emulator..."

# Démarrer l'emulator en arrière-plan
firebase emulators:start --only auth --project demo-project &
EMULATOR_PID=$!

# Attendre que l'emulator soit prêt
echo "⏳ Attente du démarrage de l'emulator..."
sleep 8

# Tester la connexion
echo "🔍 Test de connexion à l'emulator..."
if curl -s --max-time 5 http://127.0.0.1:9098 > /dev/null 2>&1; then
    echo "✅ Emulator accessible sur http://127.0.0.1:9098"
    echo "🎯 Vous pouvez maintenant tester l'application !"
    echo ""
    echo "📱 Ouvrez http://localhost:5175/ dans votre navigateur"
    echo "🔗 Allez à 'Mot de passe oublié'"
    echo "📧 Entrez un email et testez"
    echo ""
    echo "💡 Appuyez sur Ctrl+C pour arrêter l'emulator"
else
    echo "❌ Emulator non accessible"
    echo "🔍 Vérification des processus..."
    ps aux | grep firebase
fi

# Maintenir l'emulator en cours d'exécution
wait $EMULATOR_PID
