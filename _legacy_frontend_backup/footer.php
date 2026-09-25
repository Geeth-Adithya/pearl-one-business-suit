</main>

<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            &copy; <?php echo date('Y'); ?> Pearl Store. All rights reserved.
        </p>
        <div class="text-center text-xs text-gray-400 dark:text-gray-500 mt-2">
            Powered by <a href="https://www.facebook.com/share/1BADKhBsJM/" target="_blank" rel="noopener noreferrer" class="hover:underline">Pearlwaves</a>
        </div>
    </div>
</footer>

<!-- Floating Contact Button & Modal -->
<div x-data="{ contactOpen: false }" class="fixed bottom-6 right-6 z-[100]">
    <!-- Floating Button -->
    <button @click="contactOpen = true" class="bg-brand-blue hover:bg-brand-blueDark text-white rounded-full p-4 shadow-lg flex items-center justify-center transition-transform hover:scale-105">
        <ion-icon name="chatbubbles-outline" class="text-2xl"></ion-icon>
    </button>

    <!-- Modal Backdrop -->
    <div x-show="contactOpen" style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 flex items-center justify-center z-[110]"
         @click="contactOpen = false">
         
         <!-- Modal Content -->
         <div @click.stop 
              x-show="contactOpen" style="display: none;"
              x-transition:enter="transition ease-out duration-300 transform"
              x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
              x-transition:leave="transition ease-in duration-200 transform"
              x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
              x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              class="bg-gray-50 dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-[280px] overflow-hidden relative mx-4 border border-gray-200 dark:border-gray-700">
              
              <!-- Gradient Header -->
              <div class="bg-gradient-to-br from-brand-blue to-gray-800 p-6 text-center relative text-white">
                  <button @click="contactOpen = false" class="absolute top-3 right-3 text-white/80 hover:text-white transition bg-transparent border-none">
                      <ion-icon name="close-outline" class="text-xl"></ion-icon>
                  </button>
                  <div class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-full mb-2">
                      <ion-icon name="headset-outline" class="text-2xl"></ion-icon>
                  </div>
                  <h2 class="text-xl font-bold">Get in Touch</h2>
                  <p class="text-xs mt-1 text-white/80">We're here to help you</p>
              </div>

              <!-- Content Cards -->
              <div class="p-5 space-y-3">
                  <!-- WhatsApp -->
                  <a href="https://wa.me/94775768610" target="_blank" class="flex items-center p-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition border border-gray-100 dark:border-gray-700 shadow-sm group">
                      <div class="w-10 h-10 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-xl mr-3 flex-shrink-0 group-hover:scale-110 transition-transform">
                          <ion-icon name="logo-whatsapp"></ion-icon>
                      </div>
                      <div>
                          <p class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider mb-0.5">WHATSAPP</p>
                          <p class="text-gray-900 dark:text-white font-bold text-sm">+94 77 576 8610</p>
                      </div>
                  </a>

                  <!-- Facebook -->
                  <a href="https://www.facebook.com/share/1BADKhBsJM/" target="_blank" class="flex items-center p-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition border border-gray-100 dark:border-gray-700 shadow-sm group">
                      <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-xl mr-3 flex-shrink-0 group-hover:scale-110 transition-transform">
                          <ion-icon name="logo-facebook"></ion-icon>
                      </div>
                      <div>
                          <p class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider mb-0.5">FACEBOOK</p>
                          <p class="text-gray-900 dark:text-white font-bold text-sm">Pearlwaves</p>
                      </div>
                  </a>
              </div>
         </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>

