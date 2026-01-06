/**
 * OpsMind - Main JavaScript Entry Point
 *
 * NOT: Alpine.js Livewire/Flux tarafından otomatik yükleniyor (@fluxScripts).
 * Bu dosyada sadece özel modüllerimizi window'a expose ediyoruz.
 */

// TOAST UI Calendar Manager'ı import et ve global yap
import CalendarManager from './services/calendar-manager';

// Alpine.js bu sınıfa erişebilmesi için window'a ata
window.CalendarManager = CalendarManager;
