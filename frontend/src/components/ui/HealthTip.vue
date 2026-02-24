<script setup>
defineProps({
  tip: { type: String, required: true },
  index: { type: Number, required: true },
  mode: { type: String, default: 'mobile' },
})
</script>

<template>
  <Transition name="tip-slide" mode="out-in">
    <div
      :key="index"
      :class="['tip-card', mode === 'desktop' ? 'tip-desktop' : 'tip-mobile']"
    >
      <div class="tip-inner">
        <div class="tip-icon-bubble">
          <i class="mdi mdi-heart-pulse" />
        </div>
        <div class="tip-body">
          <span class="tip-label">Health Tip</span>
          <p class="tip-text">{{ tip }}</p>
        </div>
      </div>
      <div class="tip-track">
        <div class="tip-fill" :key="'fill-' + index" />
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* ── Card shell ────────────────────────────────────────────────────────────── */
.tip-card {
  background: #fff;
  border: 1px solid rgba(21, 101, 192, 0.09);
  border-radius: 16px;
  overflow: hidden;
  box-shadow:
    0 4px 20px rgba(21, 101, 192, 0.08),
    0 1px 4px rgba(0, 0, 0, 0.04);
}

.tip-desktop {
  max-width: 650px;
  margin: 0 auto;
}

.tip-mobile {
  margin-bottom: 16px;
}

/* ── Content row ───────────────────────────────────────────────────────────── */
.tip-inner {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 20px 16px;
}

/* ── Icon bubble ───────────────────────────────────────────────────────────── */
.tip-icon-bubble {
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  border-radius: 13px;
  background: linear-gradient(135deg, #1565c0 0%, #42a5f5 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 14px rgba(21, 101, 192, 0.32);
}

.tip-icon-bubble i {
  font-size: 22px;
  color: #fff;
}

/* ── Text block ────────────────────────────────────────────────────────────── */
.tip-body {
  flex: 1;
  min-width: 0;
}

.tip-label {
  display: block;
  font-family: 'Outfit', sans-serif;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: #1565c0;
  margin-bottom: 5px;
}

.tip-text {
  margin: 0;
  font-family: 'Outfit', sans-serif;
  font-size: 13.5px;
  color: #374151;
  line-height: 1.55;
}

/* ── Progress track ────────────────────────────────────────────────────────── */
.tip-track {
  height: 3px;
  background: rgba(21, 101, 192, 0.07);
}

.tip-fill {
  height: 100%;
  width: 0;
  background: linear-gradient(90deg, #1565c0, #42a5f5);
  animation: tip-expand 10s linear forwards;
}

@keyframes tip-expand {
  from { width: 0; }
  to   { width: 100%; }
}

/* ── Transition ────────────────────────────────────────────────────────────── */
.tip-slide-enter-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}

.tip-slide-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.tip-slide-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.tip-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
