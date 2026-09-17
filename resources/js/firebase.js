import { getApp, getApps, initializeApp } from "firebase/app";
import { getMessaging, getToken, isSupported, onMessage } from "firebase/messaging";

const firebaseConfig = {
    apiKey: "AIzaSyABFaF6VN8jdsjQ1KnxqeSgWIzdDd-RnRE",
    authDomain: "sbc-alumni-link.firebaseapp.com",
    projectId: "sbc-alumni-link",
    storageBucket: "sbc-alumni-link.firebasestorage.app",
    messagingSenderId: "121228610827",
    appId: "1:121228610827:web:9be380dfeb111f5b22260e",
    measurementId: "G-ZGP9VGBYRR",
};

let clientPromise;

export const getNotificationClient = () => {
    clientPromise ??= isSupported().then((supported) => {
        if (!supported) {
            return null;
        }

        const app = getApps().length ? getApp() : initializeApp(firebaseConfig);

        return { messaging: getMessaging(app), getToken, onMessage };
    }).catch((error) => {
        // A temporary storage failure must not prevent a later retry.
        clientPromise = undefined;
        throw error;
    });

    return clientPromise;
};
