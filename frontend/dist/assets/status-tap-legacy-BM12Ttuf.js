System.register(["./index-legacy-Cpk2geOD.js"],function(e,t){"use strict";var n,r,s,o,i;return{setters:[e=>{n=e.C,r=e.D,s=e.E,o=e.G,i=e.H}],execute:function(){
/*!
             * (C) Ionic http://ionicframework.com - MIT License
             */
e("startStatusTap",()=>{const e=window;e.addEventListener("statusTap",()=>{n(()=>{const t=e.innerWidth,n=e.innerHeight,a=document.elementFromPoint(t/2,n/2);if(!a)return;const c=r(a);c&&new Promise(e=>s(c,e)).then(()=>{o(async()=>{c.style.setProperty("--overflow","hidden"),await i(c,300),c.style.removeProperty("--overflow")})})})})})}}});
