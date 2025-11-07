@push('scripts')
<script>
function scheduledTaskForm() {
    return {
        autoAssign: {{ old('auto_assign', $scheduledTask->auto_assign ?? false) ? 'true' : 'false' }},
        schedules: {!! json_encode(old('schedules', $scheduledTask->schedules ?? [['type' => 'daily', 'time' => '09:00', 'days' => [], 'dates' => []]])) !!},
        weekdays: ['日', '月', '火', '水', '木', '金', '土'],
        tags: {!! json_encode(old('tags', $scheduledTask->tags ?? [])) !!},
        tagInput: '',

        init() {
            if (this.schedules.length === 0) {
                this.addSchedule();
            }
        },

        addSchedule() {
            this.schedules.push({
                type: 'daily',
                time: '09:00',
                days: [],
                dates: []
            });
        },

        removeSchedule(index) {
            if (this.schedules.length > 1) {
                this.schedules.splice(index, 1);
            }
        },

        addTag() {
            const tag = this.tagInput.trim();
            if (tag && !this.tags.includes(tag) && tag.length <= 50) {
                this.tags.push(tag);
                this.tagInput = '';
            }
        },

        removeTag(index) {
            this.tags.splice(index, 1);
        }
    }
}
</script>
@endpush