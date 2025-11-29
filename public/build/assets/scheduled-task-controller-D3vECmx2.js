class n{constructor(){this.autoAssign=!1,this.schedules=[],this.weekdays=["日","月","火","水","木","金","土"],this.tags=[],this.tagInput="",this.init()}init(){console.log("[Scheduled Task Form] Initialized");const e=document.querySelector("[data-scheduled-form]");e&&(this.loadOldValues(e),this.schedules.length===0&&this.addSchedule(),this.setupEventListeners(),this.updateUI())}loadOldValues(e){const t=e.dataset.autoAssign;t!==void 0&&(this.autoAssign=t==="true"||t==="1");const s=e.dataset.schedules;if(s)try{this.schedules=JSON.parse(s)}catch(d){console.error("Failed to parse schedules:",d),this.schedules=[]}const a=e.dataset.tags;if(a)try{this.tags=JSON.parse(a)}catch(d){console.error("Failed to parse tags:",d),this.tags=[]}}setupEventListeners(){const e=document.querySelector("[data-auto-assign]");e&&e.addEventListener("change",()=>{this.autoAssign=e.checked,this.updateUI()});const t=document.querySelector("[data-add-schedule]");t&&t.addEventListener("click",()=>this.addSchedule());const s=document.querySelector("[data-tag-input]");s&&s.addEventListener("keydown",a=>{a.key==="Enter"&&(a.preventDefault(),this.tagInput=s.value,this.addTag(),s.value="")})}updateUI(){this.updateAutoAssignUI(),this.renderSchedules(),this.renderTags()}updateAutoAssignUI(){const e=document.querySelector("[data-assigned-user-container]");e&&(this.autoAssign?e.classList.add("hidden"):e.classList.remove("hidden"))}renderSchedules(){const e=document.querySelector("[data-schedules-container]");e&&(e.innerHTML="",this.schedules.forEach((t,s)=>{const a=this.createScheduleCard(t,s);e.appendChild(a)}))}createScheduleCard(e,t){const s=document.createElement("div");return s.className="schedule-card border-2 border-gray-200 dark:border-gray-700 p-4 rounded-xl space-y-4",s.dataset.scheduleCard=t,s.innerHTML=`
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    スケジュール ${t+1}
                </h4>
                <button type="button"
                        data-remove-schedule="${t}"
                        class="p-1.5 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors ${this.schedules.length<=1?"hidden":""}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            ${this.renderScheduleTypeOptions(e,t)}
            ${this.renderWeeklyOptions(e,t)}
            ${this.renderMonthlyOptions(e,t)}
            ${this.renderTimeInput(e,t)}
        `,this.attachScheduleCardEvents(s,t),s}renderScheduleTypeOptions(e,t){return`
            <div class="space-y-3">
                <label class="schedule-type-label">
                    <input type="radio" 
                           class="schedule-type-radio"
                           name="schedules[${t}][type]" 
                           value="daily"
                           ${e.type==="daily"?"checked":""}
                           data-schedule-type="${t}"
                           required>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">毎日</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">毎日同じ時刻に実行</div>
                        </div>
                    </div>
                </label>

                <label class="schedule-type-label">
                    <input type="radio" 
                           class="schedule-type-radio"
                           name="schedules[${t}][type]" 
                           value="weekly"
                           ${e.type==="weekly"?"checked":""}
                           data-schedule-type="${t}"
                           required>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">毎週</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">曜日を指定して実行</div>
                        </div>
                    </div>
                </label>

                <label class="schedule-type-label">
                    <input type="radio" 
                           class="schedule-type-radio"
                           name="schedules[${t}][type]" 
                           value="monthly"
                           ${e.type==="monthly"?"checked":""}
                           data-schedule-type="${t}"
                           required>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">毎月</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">日付を指定して実行</div>
                        </div>
                    </div>
                </label>
            </div>
        `}renderWeeklyOptions(e,t){const s=e.type==="weekly",a=e.days||[],d=this.weekdays.map((r,i)=>{const l=a.includes(i);return`
                <label>
                    <input type="checkbox" 
                           class="weekday-checkbox"
                           name="schedules[${t}][days][]" 
                           value="${i}"
                           ${l?"checked":""}>
                    <div class="weekday-label">${r}</div>
                </label>
            `}).join("");return`
            <div data-weekly-container="${t}" class="${s?"":"hidden"} space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    曜日選択 <span class="text-red-500">*</span>
                </label>
                <div class="flex flex-wrap gap-2">
                    ${d}
                </div>
            </div>
        `}renderMonthlyOptions(e,t){const s=e.type==="monthly",a=e.dates||[],d=Array.from({length:31},(r,i)=>i+1).map(r=>{const i=a.includes(r);return`
                <label>
                    <input type="checkbox" 
                           class="date-checkbox"
                           name="schedules[${t}][dates][]" 
                           value="${r}"
                           ${i?"checked":""}>
                    <div class="date-label">${r}</div>
                </label>
            `}).join("");return`
            <div data-monthly-container="${t}" class="${s?"":"hidden"} space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    日付選択 <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-7 gap-2">
                    ${d}
                </div>
            </div>
        `}renderTimeInput(e,t){return`
            <div>
                <label for="time_${t}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    実行時刻 <span class="text-red-500">*</span>
                </label>
                <input type="time" 
                       id="time_${t}"
                       name="schedules[${t}][time]" 
                       value="${e.time||"09:00"}"
                       required
                       class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
            </div>
        `}attachScheduleCardEvents(e,t){e.querySelectorAll(`[data-schedule-type="${t}"]`).forEach(d=>{d.addEventListener("change",()=>{this.schedules[t].type=d.value,this.updateScheduleTypeVisibility(e,t)})});const a=e.querySelector(`[data-remove-schedule="${t}"]`);a&&a.addEventListener("click",()=>this.removeSchedule(t))}updateScheduleTypeVisibility(e,t){const s=this.schedules[t],a=e.querySelector(`[data-weekly-container="${t}"]`),d=e.querySelector(`[data-monthly-container="${t}"]`);a&&(s.type==="weekly"?a.classList.remove("hidden"):a.classList.add("hidden")),d&&(s.type==="monthly"?d.classList.remove("hidden"):d.classList.add("hidden"))}addSchedule(){this.schedules.push({type:"daily",time:"09:00",days:[],dates:[]}),this.updateUI()}removeSchedule(e){this.schedules.length>1&&(this.schedules.splice(e,1),this.updateUI())}renderTags(){const e=document.querySelector("[data-tags-container]");e&&(e.innerHTML="",this.tags.forEach((t,s)=>{const a=this.createTagChip(t,s);e.appendChild(a)}))}createTagChip(e,t){const s=document.createElement("div");return s.className="tag-chip",s.innerHTML=`
            <input type="hidden" name="tags[]" value="${e}">
            <span>#${e}</span>
            <button type="button" 
                    data-remove-tag="${t}"
                    class="tag-chip-remove">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `,s.querySelector(`[data-remove-tag="${t}"]`).addEventListener("click",()=>this.removeTag(t)),s}addTag(){const e=this.tagInput.trim();e&&!this.tags.includes(e)&&e.length<=50&&(this.tags.push(e),this.tagInput="",this.updateUI())}removeTag(e){this.tags.splice(e,1),this.updateUI()}}document.addEventListener("DOMContentLoaded",()=>{document.querySelector("[data-scheduled-form]")&&new n});
