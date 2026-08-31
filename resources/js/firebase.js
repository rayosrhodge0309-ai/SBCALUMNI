import { initializeApp } from "firebase/app";
import { getMessaging } from "firebase/messaging";


const firebaseConfig = {

    apiKey: "AIzaSyABFaF6VN8jdsjQ1KnxqeSgWIzdDd-RnRE",

    authDomain: "sbc-alumni-link.firebaseapp.com",

    projectId: "sbc-alumni-link",

    storageBucket: "sbc-alumni-link.firebasestorage.app",

    messagingSenderId: "121228610827",

    appId: "1:121228610827:web:9be380dfeb111f5b22260e",

    measurementId: "G-ZGP9VGBYRR"

};


const app = initializeApp(firebaseConfig);


const messaging = getMessaging(app);


export { messaging };