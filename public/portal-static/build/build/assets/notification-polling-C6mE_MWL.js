var y=(t,o)=>()=>(o||t((o={exports:{}}).exports,o),o.exports);var A=y((C,c)=>{let d=null,p=null,l=[],u=!1,a=null;document.addEventListener("DOMContentLoaded",function(){console.log("[Notification Polling] Initializing..."),v(),m()});function m(){const t=Date.now(),o=setInterval(()=>{if(window.Alpine&&(a=document.querySelector('header[x-data*="notificationCount"]'),a)){const n=Alpine.$data(a);if(n&&typeof n.notificationCount<"u"){clearInterval(o),u=!0,console.log("[Notification Polling] Alpine.js ready, starting polling"),f();return}}Date.now()-t>5e3&&(clearInterval(o),console.warn("[Notification Polling] Alpine.js timeout, starting polling anyway"),f())},100)}function v(){if(document.getElementById("notification-toast-container")){console.log("[Notification Polling] Toast container already exists");return}const t=document.createElement("div");t.id="notification-toast-container",t.style.position="fixed",t.style.bottom="24px",t.style.right="24px",t.style.zIndex="99999",t.style.display="flex",t.style.flexDirection="column",t.style.gap="12px",t.style.maxWidth="400px",t.style.pointerEvents="none",document.body.appendChild(t),console.log("[Notification Polling] Toast container created"),console.log("[Notification Polling] Container position:",t.getBoundingClientRect()),console.log("[Notification Polling] Container styles:",{position:t.style.position,bottom:t.style.bottom,right:t.style.right,zIndex:t.style.zIndex})}function f(){g(!0),d=setInterval(()=>{g(!1)},1e4),console.log("[Notification Polling] Polling started (interval: 10s)")}function h(){d&&(clearInterval(d),d=null,console.log("[Notification Polling] Polling stopped"))}async function g(t=!1){try{const o=new URLSearchParams;p&&o.append("last_checked_at",p);const n=await fetch(`/api/notifications/unread-count?${o.toString()}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!n.ok)throw new Error(`HTTP ${n.status}`);const i=await n.json();w(i.unread_count),!t&&i.new_notifications&&i.new_notifications.length>0&&b(i.new_notifications),p=i.timestamp}catch(o){console.error("[Notification Polling] Error:",o)}}function w(t){if(u){if(a||(a=document.querySelector('header[x-data*="notificationCount"]')),!a){t>0&&console.warn("[Notification Polling] Header element not found (count:",t,")");return}try{const o=Alpine.$data(a);o&&typeof o.notificationCount<"u"&&(o.notificationCount=t,console.log("[Notification Polling] Badge updated:",t))}catch(o){console.error("[Notification Polling] Failed to update badge:",o)}}}function b(t){console.log("[showToasts] Called with",t.length,"notifications"),t.length>1?(console.log("[showToasts] Showing summary toast"),k(t)):(console.log("[showToasts] Showing detail toast"),T(t[0]))}function T(t){console.log("[showDetailToast] Showing notification:",t);const o=document.getElementById("notification-toast-container");if(!o){console.error("[showDetailToast] Container not found!");return}console.log("[showDetailToast] Container found:",o);const n={important:"#ef4444",normal:"#3b82f6",info:"#6b7280"},i={important:"重要",normal:"通常",info:"情報"},e=document.createElement("div");e.className="notification-toast",e.dataset.notificationId=t.id,e.style.opacity="0",e.style.transform="translateX(100%)",e.style.transition="all 0.3s ease-out",e.style.pointerEvents="auto",e.style.minWidth="320px",e.style.cursor="pointer",e.innerHTML=`
        <div style="
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        ">
            <!-- 優先度バー（左端） -->
            <div style="
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: ${n[t.priority]||n.normal};
            "></div>
            
            <!-- コンテンツ -->
            <div style="
                padding: 12px 12px 12px 16px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
            ">
                <!-- アイコン -->
                <div style="flex-shrink: 0; margin-top: 2px;">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border-radius: 8px;
                        background: linear-gradient(135deg, #59B9C6, #3b82f6);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 12px rgba(89, 185, 198, 0.3);
                    ">
                        <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- テキスト部分 -->
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="
                            display: inline-flex;
                            align-items: center;
                            padding: 2px 8px;
                            border-radius: 6px;
                            font-size: 12px;
                            font-weight: 600;
                            background: linear-gradient(to right, #e9d5ff, #fbcfe8);
                            color: #7c3aed;
                        ">
                            ${i[t.priority]||"通常"}
                        </span>
                        <span style="
                            font-size: 12px;
                            color: #6b7280;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        ">
                            管理者: ${t.sender}
                        </span>
                    </div>
                    <p style="
                        font-weight: 600;
                        color: #111827;
                        font-size: 14px;
                        margin-bottom: 4px;
                        line-height: 1.4;
                    ">
                        ${t.title}
                    </p>
                    <p style="
                        font-size: 12px;
                        color: #6b7280;
                        display: flex;
                        align-items: center;
                        gap: 4px;
                    ">
                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        クリックして詳細を表示
                    </p>
                </div>
                
                <!-- 閉じるボタン -->
                <button class="toast-close-btn" style="
                    flex-shrink: 0;
                    color: #9ca3af;
                    padding: 4px;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s;
                    border: none;
                    background: transparent;
                " onmouseover="this.style.color='#4b5563'; this.style.background='#f3f4f6';" onmouseout="this.style.color='#9ca3af'; this.style.background='transparent';">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    `,e.addEventListener("click",s=>{s.target.closest(".toast-close-btn")||(window.location.href=`/notification/${t.id}`)}),e.querySelector(".toast-close-btn").addEventListener("click",s=>{s.stopPropagation(),r(e)}),o.appendChild(e),l.push(e),console.log("[showDetailToast] Toast added to DOM"),console.log("[showDetailToast] Toast rect:",e.getBoundingClientRect()),setTimeout(()=>{e.style.opacity="1",e.style.transform="translateX(0)",console.log("[showDetailToast] Animation started")},50),setTimeout(()=>{r(e)},5e3),l.length>3&&r(l[0])}function k(t){const o=document.getElementById("notification-toast-container");if(!o)return;const n=t.length,i=t[0],e=document.createElement("div");e.className="notification-toast",e.style.opacity="0",e.style.transform="translateX(100%)",e.style.transition="all 0.3s ease-out",e.style.pointerEvents="auto",e.style.minWidth="320px",e.style.cursor="pointer",e.innerHTML=`
        <div style="
            position: relative;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        ">
            <!-- グラデーションバー（左端） -->
            <div style="
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: linear-gradient(to bottom, #a855f7, #ec4899, #6366f1);
            "></div>
            
            <!-- コンテンツ -->
            <div style="
                padding: 12px 12px 12px 16px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
            ">
                <!-- アイコン -->
                <div style="flex-shrink: 0; margin-top: 2px;">
                    <div style="
                        position: relative;
                        width: 40px;
                        height: 40px;
                        border-radius: 8px;
                        background: linear-gradient(135deg, #a855f7, #ec4899, #6366f1);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);
                    ">
                        <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                        <!-- 件数バッジ -->
                        <div style="
                            position: absolute;
                            top: -4px;
                            right: -4px;
                            width: 20px;
                            height: 20px;
                            background: #ef4444;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-size: 11px;
                            font-weight: bold;
                            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
                        ">
                            ${n}
                        </div>
                    </div>
                </div>
                
                <!-- テキスト部分 -->
                <div style="flex: 1; min-width: 0;">
                    <p style="
                        font-weight: bold;
                        color: #7c3aed;
                        font-size: 16px;
                        margin-bottom: 4px;
                    ">
                        新着通知 ${n} 件
                    </p>
                    <p style="
                        font-size: 14px;
                        color: #374151;
                        margin-bottom: 4px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    ">
                        最新: ${i.title}
                    </p>
                    <p style="
                        font-size: 12px;
                        color: #6b7280;
                        display: flex;
                        align-items: center;
                        gap: 4px;
                    ">
                        <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        クリックして一覧を表示
                    </p>
                </div>
                
                <!-- 閉じるボタン -->
                <button class="toast-close-btn" style="
                    flex-shrink: 0;
                    color: #9ca3af;
                    padding: 4px;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s;
                    border: none;
                    background: transparent;
                " onmouseover="this.style.color='#4b5563'; this.style.background='#f3f4f6';" onmouseout="this.style.color='#9ca3af'; this.style.background='transparent';">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    `,e.addEventListener("click",s=>{s.target.closest(".toast-close-btn")||(window.location.href="/notification")}),e.querySelector(".toast-close-btn").addEventListener("click",s=>{s.stopPropagation(),r(e)}),o.appendChild(e),l.push(e),setTimeout(()=>{e.style.opacity="1",e.style.transform="translateX(0)"},50),setTimeout(()=>{r(e)},5e3),l.length>3&&r(l[0])}function r(t){!t||!t.parentElement||(t.style.opacity="0",t.style.transform="translateX(100%)",setTimeout(()=>{t.remove(),l=l.filter(o=>o!==t)},300))}window.addEventListener("beforeunload",()=>{h()});typeof c<"u"&&c.exports&&(c.exports={startPolling:f,stopPolling:h,fetchUnreadCount:g})});export default A();
