<script setup>
const props = defineProps({
  tip: {
    type: String,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  mode: {
    type: String,
    default: 'mobile',
  },
})
</script>

<template>
  <Transition name="tip-slide" mode="out-in">
    <div
      :key="index"
      :class="[
        mode === 'desktop'
          ? 'desktop-health-tip'
          : 'health-tip mobile-tip'
      ]"
    >
      <span class="tip-icon">💡</span>
      <div class="tip-text">
        <strong>Health Tip</strong>
        <p>{{ tip }}</p>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.health-tip,
.desktop-health-tip {
  background: #eef4ff;
  border-left: 4px solid #1da1ff;
  padding: 16px;
  border-radius: 12px;
  display: flex;
  gap: 12px;
  align-items: center;
  color: #3a3f47;
}

.tip-icon {
  font-size: 22px;
}

.tip-text p {
  margin: 4px 0 0;
  font-size: 14px;
  color: #555;
}

.mobile-tip {
  margin-bottom: 24px;
}

.desktop-health-tip {
  max-width: 650px;
  width: 100%;
  margin: 0 auto 32px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

.tip-slide-enter-active,
.tip-slide-leave-active {
  transition: all 0.3s ease;
}

.tip-slide-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.tip-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
