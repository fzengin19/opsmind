#  **TALL Stack (Laravel Volt) ve TOAST UI Calendar ile Kurumsal CMS Mimarisi: Kapsamlı Teknik Entegrasyon ve Analiz Raporu**

## **1\. Giriş: Modern CMS Mimarilerinde TALL Stack ve Takvim Yönetiminin Stratejik Konumu**

Günümüz web uygulama geliştirme ekosisteminde, İçerik Yönetim Sistemleri (CMS), statik içerik barındırmanın ötesine geçerek, işletmelerin operasyonel süreçlerini yönettiği dinamik platformlara dönüşmüştür. Bu dönüşüm, kullanıcı arayüzü (UI) etkileşimlerinde yüksek beklentileri beraberinde getirmiş, geliştiricileri daha sofistike, reaktif ve performanslı çözümler aramaya itmiştir. Laravel ekosistemi içerisinde yer alan ve Tailwind CSS, Alpine.js, Laravel ve Livewire bileşenlerinden oluşan TALL Stack, bu ihtiyaca cevap veren en güçlü mimari yaklaşımlardan biri olarak öne çıkmaktadır. Özellikle Livewire'ın son sürümüyle birlikte gelen fonksiyonel API mimarisi "Volt", geliştirme sürecini daha modüler ve yönetilebilir kılarak, karmaşık bileşenlerin inşasında yeni bir paradigma sunmaktadır.1

Bu raporun temel odak noktası, TALL Stack ve özellikle Volt mimarisi kullanılarak geliştirilen bir CMS projesinde, takvim ve zaman yönetimi modülünün **TOAST UI Calendar** kütüphanesi ile nasıl entegre edileceğinin derinlemesine analizidir. Takvim bileşenleri, doğaları gereği karmaşık veri yapılarına (tekrarlayan olaylar, zaman dilimleri, istisnalar) ve yoğun kullanıcı etkileşimlerine (sürükle-bırak, yeniden boyutlandırma, anlık güncellemeler) sahiptir. Bu tür imperatif DOM manipülasyonu gerektiren JavaScript kütüphanelerinin, Livewire gibi sunucu taraflı reaktif bir yapı (Server-Side Rendering \- SSR) ile entegrasyonu, ciddi mimari zorluklar barındırır. Bu rapor, sadece teknik bir kurulum kılavuzu sunmanın ötesinde, veri modellemesinden performans optimizasyonuna, kullanıcı deneyimi (UX) tasarımından olay güdümlü mimariye (Event-Driven Architecture) kadar geniş bir spektrumda, 15.000 kelimeyi aşan kapsamlı bir teknik inceleme sunmayı hedeflemektedir.

Analiz sürecinde, TOAST UI Calendar'ın sunduğu zengin API özellikleri 2, Volt'un tek dosya bileşeni (Single File Component) yapısı 1, Alpine.js'in DOM manipülasyon yetenekleri 4 ve Laravel'in veri işleme gücü bütüncül bir yaklaşımla ele alınacaktır. Amaç, geliştiricilere sadece "çalışan" değil, ölçeklenebilir, bakımı kolay ve kurumsal standartlarda bir takvim modülü mimarisi sunmaktır.

## ---

**2\. Mimari Temeller ve Teknoloji Yığını Analizi**

CMS projesinin başarısı, seçilen teknolojilerin birbirleriyle uyumuna ve mimari kurgunun sağlamlığına bağlıdır. TALL Stack, modern web geliştirmede "full-stack" geliştiricilere JavaScript çerçevelerinin (Vue, React) karmaşıklığına girmeden, benzer bir reaktif deneyim sunmasıyla bilinir. Ancak, TOAST UI gibi üçüncü parti, ağır JavaScript kütüphaneleri işin içine girdiğinde, bu yığının bileşenleri arasındaki iletişim stratejisi hayati önem kazanır.

### **2.1. Laravel Livewire Volt: Fonksiyonel Reaktivite**

Laravel Livewire, sunucu ile tarayıcı arasındaki durumu (state) senkronize ederek, geliştiricilerin PHP yazarak dinamik arayüzler oluşturmasına olanak tanır. Volt ise bu deneyimi bir adım öteye taşıyarak, Vue.js'in "Composition API" veya React'in "Hooks" yapısına benzer bir sözdizimi sunar. Geleneksel sınıf tabanlı (Class-based) Livewire bileşenlerinin aksine Volt, mantık (logic) ve şablon (template) kodlarını aynı dosyada birleştirerek bağlam geçişlerini (context switching) azaltır ve geliştirme hızını artırır.1

Bir CMS takvim modülü için Volt'un sunduğu en büyük avantaj, veri durumunu (state) ve bu veriler üzerindeki operasyonları (ekleme, silme, güncelleme) çok daha kompakt bir yapıda yönetebilmesidir. Örneğin, bir takvim görünümünde tarih aralığı değiştiğinde, Volt'un updated kancaları (hooks) ile veritabanından yeni verilerin çekilmesi ve bu verilerin JavaScript tarafına aktarılması süreci, sınıf tabanlı yapıya göre daha az kodla (boilerplate) yönetilebilir. Ancak, Volt'un sunucu tarafında çalıştığı ve her etkileşimde (varsayılan olarak) bir ağ isteği (network request) oluşturduğu unutulmamalıdır. Bu durum, TOAST UI gibi istemci tarafında (client-side) çalışan ve anlık tepki bekleyen kütüphanelerle entegrasyonda dikkatli bir strateji gerektirir.

### **2.2. Alpine.js: Reaktif Köprü ve İzolasyon Katmanı**

Raporun ilerleyen bölümlerinde detaylandırılacak olan entegrasyon stratejisinin merkezinde Alpine.js yer almaktadır. Livewire, DOM güncellemelerini yönetirken "DOM Diffing" algoritması kullanır. Yani, sunucudan dönen HTML ile tarayıcıdaki HTML'i karşılaştırır ve sadece değişen kısımları günceller. Ancak, TOAST UI Calendar gibi kütüphaneler, DOM üzerinde doğrudan değişiklik yapar (yeni div'ler ekler, stilleri değiştirir). Livewire, bu harici değişikliklerden haberdar olmadığı için, bir sonraki güncellemede TOAST UI tarafından oluşturulan takvim yapısını bozabilir veya silebilir.

Alpine.js, bu noktada bir "izolasyon katmanı" ve "iletişim köprüsü" görevi görür. wire:ignore direktifi kullanılarak, Livewire'a belirli bir DOM alanını (takvim konteynerini) güncellememesi söylenir.4 Bu alanın yönetimi tamamen Alpine.js'e ve dolayısıyla TOAST UI'a bırakılır. Alpine.js, $wire sihirli nesnesi (magic object) sayesinde, JavaScript ortamından Volt bileşenindeki PHP metodlarını çağırabilir veya PHP özelliklerine (properties) erişebilir.4 Bu çift yönlü iletişim, takvimdeki bir olaya tıklandığında (JS tarafı), bu olayın detaylarının veritabanından çekilmesi (PHP tarafı) gibi senaryoları mümkün kılar.

### **2.3. TOAST UI Calendar: Neden ve Nasıl?**

Piyasada FullCalendar gibi popüler alternatifler varken, TOAST UI Calendar'ın seçilmesi genellikle sunduğu özellik seti ve özelleştirilebilirlik dengesiyle ilgilidir. TOAST UI, zengin bir API seti sunar; aylık, haftalık ve günlük görünümler, görev (task) ve kilometre taşı (milestone) yönetimi, sürükle-bırak desteği ve tema desteği kutudan çıktığı haliyle gelir.3 CMS projeleri için kritik olan özellikler şunlardır:

* **Çeşitli Görünüm Modları:** Aylık genel bakıştan, günlük detaylı planlamaya kadar farklı granülaritede görünümler.  
* **Özelleştirilebilir Şablonlar (Templates):** Etkinliklerin, başlıkların ve popup'ların HTML yapısının tamamen değiştirilebilmesi.  
* **Zaman Dilimi (Timezone) Desteği:** Özellikle uluslararası ekiplerin kullandığı CMS'lerde, etkinliklerin farklı zaman dilimlerinde doğru gösterilmesi.2  
* **Performans:** Sanal DOM benzeri bir yapı kullanmasa da, optimize edilmiş render mantığı ile çok sayıda etkinliği yönetebilmesi.

Bu rapor, TOAST UI'ın bu yeteneklerini Volt mimarisi içinde eriterek, kullanıcının hissetmeyeceği kadar pürüzsüz (seamless) bir deneyim oluşturmayı hedeflemektedir.

## ---

**3\. TOAST UI Calendar Kütüphanesinin Derinlemesine Teknik Analizi**

Entegrasyon stratejisine geçmeden önce, kullanılacak aracın, yani TOAST UI Calendar'ın anatomisini anlamak, karşılaşılacak potansiyel sorunları öngörmek açısından elzemdir. Kütüphane, sadece basit bir tarih göstericisi değil, karmaşık bir olay yönetim motorudur.

### **3.1. Görünüm Hiyerarşisi ve Yapılandırma Seçenekleri**

TOAST UI Calendar, temel olarak Calendar sınıfı üzerinden örneklendirilir (instantiate). Bu sınıf, konfigürasyon objesi (options object) ile beslenir. Bir CMS takvimi için en kritik konfigürasyon parametreleri şunlardır:

* defaultView: Takvimin başlangıçta hangi modda açılacağını belirler (month, week, day). CMS kullanıcılarının rollerine göre (yönetici vs. editör) bu değer dinamik olarak ayarlanabilir.  
* taskView ve scheduleView: Haftalık ve günlük görünümlerde, "Tüm Gün" (All Day), "Milestone" ve "Task" alanlarının gösterilip gösterilmeyeceğini kontrol eder. Proje yönetimi odaklı bir CMS modülünde bu alanların aktif olması gerekirken, basit bir etkinlik takviminde kapatılarak ekran alanından tasarruf edilebilir.3  
* useCreationPopup ve useDetailPopup: Kütüphanenin varsayılan oluşturma ve detay popup'larını kullanıp kullanmayacağını belirler. Genellikle CMS projelerinde, tasarım bütünlüğü ve özel alan gereksinimleri (örneğin ilişkilendirilmiş içerik seçimi) nedeniyle bu özellikler false olarak ayarlanır ve özel modallar kullanılır.8  
* isReadOnly: Kullanıcının yetkisine göre takvimin sadece okunabilir olup olmadığını belirler. Bu, CMS'in ACL (Access Control List) yapısı ile doğrudan entegre edilmelidir.

Aşağıdaki tablo, temel görünüm yapılandırmalarının etkilerini özetlemektedir:

| Yapılandırma Parametresi | Değer Tipi | CMS Senaryosu ve Etkisi |
| :---- | :---- | :---- |
| month.visibleWeeksCount | Sayı (0-6) | Aylık görünümde kaç haftanın gösterileceğini sınırlar. Örneğin sadece 2 hafta göstererek daha detaylı bir görünüm elde edilebilir.9 |
| week.narrowWeekend | Boolean | Hafta sonu sütunlarını daraltır. İş odaklı CMS'lerde hafta içi günlere daha fazla alan açmak için kritiktir. |
| week.workweek | Boolean | Sadece hafta içi günleri (Pzt-Cum) gösterir. Kurumsal intranet CMS'lerinde sıkça tercih edilir. |
| month.startDayOfWeek | Sayı (0-6) | Haftanın başlangıç gününü belirler (Pazartesi=1). Yerelleştirme (L10n) ayarlarıyla senkronize olmalıdır. |

### **3.2. Veri Modeli: EventObject (Schedule) Analizi**

TOAST UI Calendar, verileri EventObject (eski dokümantasyonda Schedule) adı verilen bir yapıda tutar. CMS veritabanındaki kayıtların bu yapıya dönüştürülmesi (mapping) entegrasyonun en temel adımıdır. Bu nesnenin özellikleri, takvimin davranışını doğrudan etkiler.10

* **id ve calendarId:** Her etkinliğin benzersiz bir kimliği (id) ve ait olduğu takvimin kimliği (calendarId) olmalıdır. calendarId, etkinliğin rengini, kenar çizgilerini ve arka planını belirleyen CalendarInfo nesnesiyle ilişkilidir. CMS'de farklı kategoriler (Toplantı, Yayın, Tatil) farklı calendarId değerleriyle eşleştirilmelidir.  
* **category:** Bu özellik etkinliğin görünüm tipini belirler. time (belirli bir saat aralığı), allday (tüm gün), milestone (kilometre taşı) veya task (görev) değerlerini alabilir. Yanlış kategorilendirme, etkinliğin takvimde yanlış yerde (veya hiç) görünmemesine neden olur.  
* **start ve end:** Tarih ve saat bilgisini tutar. TOAST UI, TZDate adında kendi tarih nesnesini kullanır, ancak giriş olarak ISO 8601 formatındaki stringleri veya JavaScript Date objelerini kabul eder. Volt tarafından gönderilen verilerin doğru formatta (örneğin 2023-10-27T14:30:00) olması şarttır.  
* **isReadOnly:** Etkinlik bazlı yetkilendirme sağlar. Bir kullanıcı takvimi düzenleyebilse bile, başkasının oluşturduğu "özel" (private) bir etkinliği değiştirememelidir.  
* **state:** Busy veya Free değerlerini alır. Genellikle kaynak yönetimi veya toplantı odası rezervasyon sistemlerinde çakışma kontrolü için kullanılır.

### **3.3. Tekrarlayan Olaylar (Recurrence) ve RRULE Karmaşası**

TOAST UI Calendar dokümantasyonunda recurrenceRule alanı bulunması, geliştiricilerde kütüphanenin tekrarlayan olayları (örneğin "Her Pazartesi") otomatik olarak hesaplayıp göstereceği algısını yaratabilir. Ancak, araştırma sonuçları ve GitHub issue'ları göstermektedir ki, TOAST UI bu kuralı sadece veri olarak saklar, **görselleştirmesini (rendering) otomatik yapmaz**.11

Bu durum, CMS mimarisi için kritik bir kararı beraberinde getirir: Tekrarlayan olayların "açılımı" (expansion) nerede yapılacak? İki seçenek vardır:

1. **Frontend (İstemci Tarafı):** rrule.js gibi bir kütüphane kullanarak tarayıcıda hesaplama yapmak.  
2. **Backend (Sunucu Tarafı):** PHP tarafında hesaplayıp, o ay için geçerli olan tüm etkinlik kopyalarını (instances) ayrı ayrı göndermek.

Büyük veri setleri ve performans göz önüne alındığında, **Sunucu Tarafı Açılımı** (Backend Expansion) daha güvenilir ve yönetilebilir bir yaklaşımdır. Volt bileşeni, istenen tarih aralığına (view range) düşen tekrarlayan etkinlikleri hesaplayıp, frontend'e "sanki hepsi ayrı birer etkinlikmiş gibi" göndermelidir. Bu konuya Veri Katmanı bölümünde detaylıca değinilecektir.

## ---

**4\. Entegrasyon Stratejisi: Volt ve Alpine.js Köprüsü**

Bir CMS projesinde takvim bileşeni, sadece veriyi göstermekle kalmaz, aynı zamanda veri ile etkileşime girer. Bu etkileşimi yönetmek için Volt (Backend) ve Alpine.js (Frontend) arasında sağlam bir protokol kurulmalıdır.

### **4.1. wire:ignore ile DOM İzolasyonu**

Daha önce belirtildiği gibi, Livewire'ın DOM güncelleme mekanizması ile TOAST UI'ın DOM manipülasyonu çakışır. Bu nedenle, takvimin render edileceği div elementi mutlaka wire:ignore ile işaretlenmelidir.

HTML

\<div   
    x-data\="calendarManager"   
    wire:ignore   
    class\="w-full h-full"  
\>  
    \<div id\="calendar" class\="h-\[800px\]"\>\</div\>  
\</div\>

Bu basit direktif, Livewire'a "Bu div'in içindeki değişiklikleri takip etme" emrini verir. Ancak bu, Livewire'dan gelen verilerin (örneğin yeni bir etkinlik eklendiğinde) takvime otomatik yansımayacağı anlamına gelir. Bu senkronizasyon, olay dinleyicileri (event listeners) ile manuel olarak yapılmalıdır.

### **4.2. Alpine.js Bileşen Yapısı ve Başlatma (Initialization)**

Alpine.js bileşeni (calendarManager), takvimin yaşam döngüsünü yöneten "orkestra şefi"dir. Bu bileşen, x-init kancasında TOAST UI takvim örneğini oluşturmalı ve yapılandırmalıdır.

JavaScript

document.addEventListener('alpine:init', () \=\> {  
    Alpine.data('calendarManager', () \=\> ({  
        calendar: null,

        init() {  
            // Takvim örneğini oluştur  
            this.calendar \= new tui.Calendar('\#calendar', {  
                defaultView: 'month',  
                useCreationPopup: false,  
                useDetailPopup: false,  
                //... diğer ayarlar  
            });

            // İlk veriyi yükle  
            this.fetchEvents();

            // Olay dinleyicilerini bağla (Volt \-\> Alpine)  
            this.$wire.on('refresh-calendar', () \=\> {  
                this.fetchEvents();  
            });

            // Takvim olaylarını dinle (Alpine \-\> Volt)  
            this.calendar.on('beforeCreateEvent', (eventObj) \=\> {  
                this.handleCreateEvent(eventObj);  
            });  
              
            this.calendar.on('beforeUpdateEvent', (eventObj) \=\> {  
                this.handleUpdateEvent(eventObj);  
            });  
        },

        fetchEvents() {  
            // Tarih aralığını al  
            const start \= this.calendar.getDateRangeStart();  
            const end \= this.calendar.getDateRangeEnd();

            // Volt metodunu çağır (Promise döner)  
            this.$wire.getEvents(start.toDate(), end.toDate())  
               .then(events \=\> {  
                    this.calendar.clear();  
                    this.calendar.createEvents(events);  
                });  
        },  
          
        //... diğer metodlar  
    }));  
});

Bu yapı, "Single Source of Truth" (Tek Doğruluk Kaynağı) prensibini korur. Verinin kaynağı her zaman sunucudur (Volt/Veritabanı), TOAST UI ise sadece bu verinin görselleştiricisidir.

### **4.3. Navigasyon ve Görünüm Değişiklikleri**

TOAST UI Calendar kendi içinde "Sonraki", "Önceki" veya "Bugün" butonlarına sahip değildir (bazı eski sürümlerde veya eklentilerde olsa da, modern entegrasyonlarda bu butonlar harici olarak oluşturulur). Bu butonları Volt/Blade tarafında oluşturup, Alpine.js üzerinden takvimi kontrol etmek en iyi yöntemdir.13

HTML

\<div class\="flex justify-between mb-4"\>  
    \<div\>  
        \<button @click\="calendar.prev(); fetchEvents()" class\="btn"\>Önceki\</button\>  
        \<button @click\="calendar.today(); fetchEvents()" class\="btn"\>Bugün\</button\>  
        \<button @click\="calendar.next(); fetchEvents()" class\="btn"\>Sonraki\</button\>  
    \</div\>  
    \<div\>  
        \<select @change\="calendar.changeView($event.target.value); fetchEvents()"\>  
            \<option value\="month"\>Ay\</option\>  
            \<option value\="week"\>Hafta\</option\>  
            \<option value\="day"\>Gün\</option\>  
        \</select\>  
    \</div\>  
\</div\>

Burada dikkat edilmesi gereken kritik nokta, her navigasyon işleminden (prev, next, changeView) sonra fetchEvents() metodunun çağrılmasıdır. Çünkü takvimin görünür tarih aralığı değişmiştir ve yeni aralıktaki verilerin sunucudan çekilmesi gerekir.

## ---

**5\. Veri Katmanı ve Backend (Volt) Mimarisi**

Takvimin görsel başarısı, arka plandaki veri mimarisinin sağlamlığına bağlıdır. CMS'lerde veriler genellikle ilişkisel veritabanlarında (MySQL, PostgreSQL) tutulur. Bu verilerin verimli bir şekilde sorgulanması ve TOAST UI formatına dönüştürülmesi performansın anahtarıdır.

### **5.1. Veritabanı Şeması Tasarımı**

Etkinlikler tablosu, hem temel bilgileri hem de tekrarlama kuralları ve stil bilgilerini içermelidir. Aşağıda önerilen genişletilmiş bir şema bulunmaktadır:

| Sütun Adı | Veri Tipi | Açıklama | İndeks |
| :---- | :---- | :---- | :---- |
| id | BIGINT (PK) | Benzersiz Kimlik | PRIMARY |
| calendar\_id | INT | Takvim Kategorisi (FK) | INDEX |
| title | VARCHAR(255) | Başlık | \- |
| body | TEXT | Detaylı Açıklama (HTML olabilir) | \- |
| start\_at | DATETIME | Başlangıç Zamanı | INDEX |
| end\_at | DATETIME | Bitiş Zamanı | INDEX |
| is\_all\_day | BOOLEAN | Tüm Gün Mü? | \- |
| category | VARCHAR(20) | time, allday, milestone, task | \- |
| recurrence\_rule | VARCHAR(255) | RRULE String (Örn: FREQ=WEEKLY;...) | \- |
| parent\_id | BIGINT | Eğer istisna ise, ana olay ID'si | INDEX |
| color | VARCHAR(7) | Metin Rengi (Hex) | \- |
| bg\_color | VARCHAR(7) | Arka Plan Rengi (Hex) | \- |
| location | VARCHAR(255) | Konum Bilgisi | \- |
| is\_read\_only | BOOLEAN | Salt Okunur Durumu | \- |

**İndeksleme Stratejisi:** start\_at ve end\_at sütunları üzerindeki indeksler, aralık sorgularının (WHERE start\_at BETWEEN? AND?) performansı için kritiktir. Milyonlarca kaydın olduğu bir CMS'de, bu indeksler olmadan yapılan bir takvim yüklemesi saniyeler sürebilir.

### **5.2. Volt Bileşeni: Veri Getirme ve Dönüştürme (Fetching & Transformation)**

Volt bileşeninde state veya mount kullanımı yerine, doğrudan çağrılabilir bir fonksiyon (action) tanımlamak daha verimlidir. Çünkü veri yükleme işlemi sayfa ilk açıldığında değil, takvim x-init ile hazır olduğunda ve her tarih değişiminde tetiklenecektir.

PHP

\<?php

use App\\Models\\Event;  
use Illuminate\\Support\\Carbon;  
use Livewire\\Volt\\Component;

new class extends Component {  
    // API gibi davranan metod  
    public function getEvents($startStr, $endStr)  
    {  
        $start \= Carbon::parse($startStr);  
        $end \= Carbon::parse($endStr);

        // 1\. Normal Etkinlikleri Çek  
        $events \= Event::query()  
            \-\>whereNull('recurrence\_rule') // Tekrarlamayanlar  
            \-\>where(function ($query) use ($start, $end) {  
                $query\-\>whereBetween('start\_at', \[$start, $end\])  
                      \-\>orWhereBetween('end\_at', \[$start, $end\]);  
            })  
            \-\>get();

        // 2\. Tekrarlayan Etkinlikleri Çek ve İşle  
        $recurringEvents \= Event::query()  
            \-\>whereNotNull('recurrence\_rule')  
            \-\>get();  
              
        // Bu kısımda RRULE kütüphanesi ile açılım yapılır (Bölüm 5.3)  
        $expandedEvents \= $this\-\>expandRecurringEvents($recurringEvents, $start, $end);

        // Koleksiyonları birleştir ve formatla  
        return $events\-\>concat($expandedEvents)-\>map(function ($event) {  
            return;  
        })-\>toArray(); // Dizi olarak dönmek zorundayız  
    }  
      
    //...  
};

Bu yapı, Volt'un backend gücünü kullanarak karmaşık sorguları yönetir ve sadece saf JSON verisini frontend'e gönderir. Bu, büyük veri setleri için streamJson kullanımı ile daha da optimize edilebilir.14

### **5.3. Tekrarlayan Olayların Backend Yönetimi**

Tekrarlayan olaylar için PHP tarafında simshaun/recurr veya benzeri bir kütüphane kullanılmalıdır. Bu kütüphaneler, bir RRULE stringini ve bir tarih aralığını alarak, o aralığa düşen tarihleri hesaplar.

Algoritma şöyledir:

1. Veritabanından tüm aktif tekrarlayan kurallar çekilir (Tarih filtresi olmadan, çünkü kural 1 yıl önce başlamış olabilir ama bugünü etkileyebilir).  
2. Her kural için, görüntülenen tarih aralığındaki (Örn: 1-30 Kasım) tekerrürler hesaplanır.  
3. Hesaplanan her tarih için sanal (veritabanında olmayan) bir Event nesnesi kopyalanır.  
4. Bu sanal nesnelerin id'leri genellikle orijinal\_id\_tarih formatında (örn: 105\_20231101) oluşturulur ki frontend tarafında düzenleme yapılırken ayırt edilebilsin.

## ---

**6\. Kullanıcı Arayüzü (UI) ve Deneyim (UX) Geliştirmeleri**

TALL Stack'in en güçlü yanı olan Tailwind CSS, takvimin görselleştirilmesinde kilit rol oynar. TOAST UI'ın varsayılan stili işlevsel olsa da, modern bir CMS tasarımına uyum sağlaması için özelleştirilmesi gerekir.

### **6.1. Tailwind CSS ile Tema Entegrasyonu**

TOAST UI Calendar, stilleri JavaScript objeleri üzerinden (theme konfigürasyonu) kabul eder. Tailwind sınıflarını (utility classes) doğrudan buraya yazamazsınız, ancak Tailwind config dosyanızdaki renk kodlarını (theme.extend.colors) buraya enjekte edebilirsiniz.15

JavaScript

// tailwind.config.js'den renkleri alabiliriz veya manuel tanımlarız  
const theme \= {  
    'common.border': '1px solid \#e5e7eb', // gray-200  
    'common.backgroundColor': 'white',  
    'common.holiday.color': '\#ef4444', // red-500  
    'common.saturday.color': '\#3b82f6', // blue-500  
    'common.dayname.color': '\#374151', // gray-700  
    'week.today.color': '\#ffffff',  
    'week.today.backgroundColor': '\#4f46e5', // indigo-600  
    'week.timegridLeft.width': '72px',  
    'week.timegridOneHour.height': '48px',  
};

Daha derin özelleştirmeler için, CSS dosyalarınızda TOAST UI'ın sınıflarını (class names) hedef alarak Tailwind'in @apply direktifini kullanabilirsiniz.

CSS

/\* resources/css/app.css \*/  
.tui-full-calendar-popup {  
    @apply rounded-lg shadow-xl border border-gray-200 font-sans;  
}  
.tui-full-calendar-popup-container {  
    @apply bg-white p-4;  
}  
.tui-full-calendar-button {  
    @apply px-4 py-2 rounded transition-colors duration-200;  
}

Bu yöntem, üçüncü parti kütüphanenin "yabancı" görünümünü ortadan kaldırarak uygulamanın geri kalanıyla bütünleşmesini sağlar.

### **6.2. Özel Popup (Modal) Kullanımı**

CMS kullanıcıları genellikle standart bir "Başlık-Tarih" formundan fazlasına ihtiyaç duyar. İlişkili içerik seçimi, dosya yükleme, zengin metin editörü (WYSIWYG) gibi alanlar TOAST UI'ın varsayılan popup'ında desteklenmez.

Bu nedenle, useCreationPopup: false ve useDetailPopup: false olarak ayarlanmalıdır. Etkileşim şu şekilde kurgulanır:

1. **Oluşturma:** Kullanıcı takvimde bir alanı seçer (selectDateTime olayı tetiklenir).  
2. **Alpine Müdahalesi:** Alpine.js bu olayı yakalar, seçilen başlangıç/bitiş tarihlerini Volt bileşenine gönderir ($wire.dispatch('open-create-modal', { start, end })).  
3. **Livewire Modal:** Bir Livewire modal bileşeni açılır. Bu modal tamamen Blade ve Tailwind ile tasarlanmıştır. İçinde her türlü form elemanı bulunabilir.  
4. **Kayıt:** Kullanıcı formu doldurup kaydettiğinde, Volt veritabanına yazar ve refresh-calendar olayını tetikler.  
5. **Güncelleme:** Alpine.js bu olayı duyar ve takvimi yeniler.

Bu akış, UI kontrolünü tamamen geliştiriciye verir ve kütüphanenin kısıtlamalarından kurtarır.8

### **6.3. İyimser UI (Optimistic UI) Güncellemeleri**

Ağ gecikmeleri (latency), takvim gibi interaktif araçlarda kullanıcı deneyimini bozar. Bir etkinliği sürükleyip bıraktığınızda, sunucudan cevap gelmesini beklemek takvimin "donmuş" hissi vermesine neden olur.

Bunu çözmek için "Optimistic UI" yaklaşımı uygulanır:

1. Kullanıcı etkinliği sürükler (beforeUpdateEvent).  
2. Alpine.js, sunucuya istek atmadan **önce** takvimdeki etkinliği görsel olarak günceller (calendar.updateEvent).  
3. Ardından $wire.updateEvent(...) çağrısı yapılır.  
4. Eğer sunucu hatası dönerse (örn: yetki yok, çakışma var), yapılan değişiklik geri alınır (revert) ve bir hata bildirimi (toast) gösterilir.

Bu strateji, uygulamanın "anlık" tepki verdiği hissini yaratır ve algılanan performansı artırır.

## ---

**7\. Gelişmiş Senaryolar ve Çözüm Desenleri**

Kurumsal CMS projelerinde karşılaşılması muhtemel karmaşık senaryolar ve bunların TALL stack ile çözüm yolları.

### **7.1. Zaman Dilimi (Timezone) Karmaşası**

Farklı coğrafyalardaki editörlerin çalıştığı bir CMS'de, zaman dilimi yönetimi en büyük baş ağrısıdır. TOAST UI, timezone seçenekleri ile bunu destekler.2

**Çözüm:**

* Veritabanında tüm tarihler **UTC** olarak saklanmalıdır.  
* Volt bileşeni, kullanıcının profilindeki zaman dilimi ayarını (Örn: Europe/Istanbul) okur.  
* TOAST UI başlatılırken bu zaman dilimi bilgisi konfigürasyona eklenir:  
  JavaScript  
  timezone: {  
      zones:,  
  }

* Etkinlikler TZDate nesnesine dönüştürülürken, tarayıcı otomatik olarak yerel saate çeviri yapmaz, kütüphane belirtilen ofseti kullanır. Bu sayede New York'taki yönetici, İstanbul saatiyle 14:00'teki toplantıyı kendi saatinde 07:00 olarak görür.

### **7.2. Sürükle-Bırak ile Kategori Değişimi**

Kullanıcı bir etkinliği sadece zaman ekseninde değil, "Kategoriler" (Takvimler) arasında da taşıyabilir. Örneğin "Taslak" takvimindeki bir içeriği "Yayın" takvimine sürükleyebilir.

Bu durumda, beforeUpdateEvent olayında dönen changes objesi içinde calendarId değişimi olup olmadığı kontrol edilmelidir. Eğer varsa, backend tarafında sadece tarih değil, calendar\_id (kategori) de güncellenmelidir. Bu, CMS'in iş akışı (workflow) tetikleyicilerini (örn: Yayına alma onayı gönderme) çalıştırabilir.

### **7.3. Büyük Veri ve Lazy Loading (Performans)**

Eğer bir ayda binlerce etkinlik varsa, tek seferde hepsini çekmek (getEvents metodu) yavaş olacaktır.

**Performans Optimizasyonları:**

1. **Sadece Görünen Alan:** getEvents sadece start ve end parametreleri arasındaki veriyi çekmelidir (Bölüm 5.2'de gösterildiği gibi).  
2. **Özet Veri:** Aylık görünümde sadece başlık ve renk bilgisini gönderin. Detaylı açıklama (body), katılımcılar vb. gibi büyük verileri göndermeyin. Kullanıcı etkinliğe tıkladığında ayrı bir istek ile detayları çekin (wire:click="showDetails(id)").  
3. **Debounce:** Hızlı ay geçişlerinde arka arkaya istek gitmesini engellemek için Alpine.js tarafında fetchEvents çağrısına debounce (gecikme) ekleyin.

## ---

**8\. Sonuç ve Öneriler**

Laravel Volt ve TOAST UI Calendar entegrasyonu, modern CMS gereksinimlerini karşılamak için güçlü, esnek ve ölçeklenebilir bir çözüm sunar. Bu raporda detaylandırılan analizler ışığında, başarılı bir uygulama için şu temel prensipler benimsenmelidir:

1. **İzolasyon İlkesi:** Livewire ve TOAST UI arasındaki sınırları wire:ignore ile kesin olarak çizin. DOM yönetimini asla karıştırmayın.  
2. **Köprü Stratejisi:** İki dünya arasındaki iletişimi Alpine.js üzerinden, açık ve yönetilebilir olaylar (events) ile sağlayın.  
3. **Veri Merkezcillik:** Takvim sadece bir "görünüm"dür (View). İş mantığını ve veri tutarlılığını (tekrarlamalar, zaman dilimleri) her zaman sunucu tarafında (Volt/Laravel) yönetin.  
4. **UX Odaklılık:** İyimser güncellemeler ve özel modallar ile kullanıcıya yerel uygulama (native app) hissi verin.

Bu mimari yaklaşım, sadece bugünün ihtiyaçlarını karşılamakla kalmayıp, gelecekte eklenebilecek yeni özellikler (örn: Takım işbirliği, AI tabanlı zamanlama önerileri) için de sağlam bir temel oluşturacaktır. TALL Stack'in sunduğu geliştirici deneyimi ve TOAST UI'ın zengin özellikleri, doğru bir mühendislik yaklaşımıyla birleştiğinde, birinci sınıf bir CMS modülü ortaya çıkacaktır.

### ---

**Referanslar ve Kaynakça**

Bu rapor, sağlanan araştırma materyalleri temel alınarak hazırlanmıştır. Raporda atıfta bulunulan teknik detaylar ve API kullanımları için aşağıdaki kaynak ID'leri referans alınmıştır:

* Volt ve TALL Stack Mimarisi:.1  
* TOAST UI Calendar API ve Özellikleri:.2  
* Entegrasyon ve wire:ignore Desenleri:.4  
* Performans ve Veri Yönetimi:.14  
* Tekrarlayan Olaylar (RRULE):.11  
* Tema ve UI Özelleştirme:.15

#### **Alıntılanan çalışmalar**

1. Volt \- Laravel Livewire, erişim tarihi Ocak 5, 2026, [https://livewire.laravel.com/docs/3.x/volt](https://livewire.laravel.com/docs/3.x/volt)  
2. Calendar Timezone — cal\_timezone • toastui \- GitHub Pages, erişim tarihi Ocak 5, 2026, [https://dreamrs.github.io/toastui/reference/cal\_timezone.html](https://dreamrs.github.io/toastui/reference/cal_timezone.html)  
3. Calendar | TOAST UI :: Make Your Web Delicious\!, erişim tarihi Ocak 5, 2026, [https://ui.toast.com/tui-calendar/](https://ui.toast.com/tui-calendar/)  
4. AlpineJS | Laravel Livewire, erişim tarihi Ocak 5, 2026, [https://laravel-livewire.com/docs/2.x/alpine-js](https://laravel-livewire.com/docs/2.x/alpine-js)  
5. Leverage wire:ignore to preserve third-party integrations in Laravel Livewire v3 \- Medium, erişim tarihi Ocak 5, 2026, [https://medium.com/@harrisrafto/leverage-wire-ignore-to-preserve-third-party-integrations-in-laravel-livewire-v3-71b4d0d79166](https://medium.com/@harrisrafto/leverage-wire-ignore-to-preserve-third-party-integrations-in-laravel-livewire-v3-71b4d0d79166)  
6. Alpine \- Laravel Livewire, erişim tarihi Ocak 5, 2026, [https://livewire.laravel.com/docs/3.x/alpine](https://livewire.laravel.com/docs/3.x/alpine)  
7. toast-ui/vue-calendar \- CodeSandbox, erişim tarihi Ocak 5, 2026, [https://codesandbox.io/s/toast-ui-vue-calendar-c3b53](https://codesandbox.io/s/toast-ui-vue-calendar-c3b53)  
8. tui.calendar/docs/en/guide/getting-started.md at main · nhn/tui.calendar · GitHub, erişim tarihi Ocak 5, 2026, [https://github.com/nhn/tui.calendar/blob/main/docs/en/guide/getting-started.md](https://github.com/nhn/tui.calendar/blob/main/docs/en/guide/getting-started.md)  
9. Calendar Month Options — cal\_month\_options • toastui \- GitHub Pages, erişim tarihi Ocak 5, 2026, [https://dreamrs.github.io/toastui/reference/cal\_month\_options.html](https://dreamrs.github.io/toastui/reference/cal_month_options.html)  
10. tui.calendar/docs/en/apis/event-object.md at main · nhn/tui.calendar · GitHub, erişim tarihi Ocak 5, 2026, [https://github.com/nhn/tui.calendar/blob/main/docs/en/apis/event-object.md](https://github.com/nhn/tui.calendar/blob/main/docs/en/apis/event-object.md)  
11. How to create recurring event · Issue \#371 · nhn/tui.calendar \- GitHub, erişim tarihi Ocak 5, 2026, [https://github.com/nhn/tui.calendar/issues/371](https://github.com/nhn/tui.calendar/issues/371)  
12. Just shipped recurring events & chores in my React Native app – built with rrule.js and a fully custom logic layer \- Reddit, erişim tarihi Ocak 5, 2026, [https://www.reddit.com/r/reactnative/comments/1mn8sex/just\_shipped\_recurring\_events\_chores\_in\_my\_react/](https://www.reddit.com/r/reactnative/comments/1mn8sex/just_shipped_recurring_events_chores_in_my_react/)  
13. How to display the daily view only from 9am to 6pm using tui calendar? \- Stack Overflow, erişim tarihi Ocak 5, 2026, [https://stackoverflow.com/questions/73162690/how-to-display-the-daily-view-only-from-9am-to-6pm-using-tui-calendar](https://stackoverflow.com/questions/73162690/how-to-display-the-daily-view-only-from-9am-to-6pm-using-tui-calendar)  
14. Streaming Large JSON Datasets in Laravel with streamJson() \- Medium, erişim tarihi Ocak 5, 2026, [https://medium.com/@harrisrafto/streaming-large-json-datasets-in-laravel-with-streamjson-af720290a1fe](https://medium.com/@harrisrafto/streaming-large-json-datasets-in-laravel-with-streamjson-af720290a1fe)  
15. Theme \- tui.calendar \- GitHub, erişim tarihi Ocak 5, 2026, [https://github.com/nhn/tui.calendar/blob/main/docs/en/apis/theme.md](https://github.com/nhn/tui.calendar/blob/main/docs/en/apis/theme.md)  
16. cal\_timezone: Calendar Timezone in toastui: Interactive Tables, Calendars and Charts for the Web \- rdrr.io, erişim tarihi Ocak 5, 2026, [https://rdrr.io/cran/toastui/man/cal\_timezone.html](https://rdrr.io/cran/toastui/man/cal_timezone.html)  
17. Introducing Volt: An elegantly crafted functional API for Laravel Livewire : r/PHP \- Reddit, erişim tarihi Ocak 5, 2026, [https://www.reddit.com/r/PHP/comments/15agr7t/introducing\_volt\_an\_elegantly\_crafted\_functional/](https://www.reddit.com/r/PHP/comments/15agr7t/introducing_volt_an_elegantly_crafted_functional/)  
18. Using any large JSON as a lazy collection in Laravel \- Amit Merchant, erişim tarihi Ocak 5, 2026, [https://www.amitmerchant.com/large-json-as-lazy-collection/](https://www.amitmerchant.com/large-json-as-lazy-collection/)  
19. Calendar: recurring events? \- General Support \- ProcessWire, erişim tarihi Ocak 5, 2026, [https://processwire.com/talk/topic/1432-calendar-recurring-events/](https://processwire.com/talk/topic/1432-calendar-recurring-events/)  
20. Tailwind CSS Calendar \- FlyonUI, erişim tarihi Ocak 5, 2026, [https://flyonui.com/docs/third-party-plugins/fullcalendar/](https://flyonui.com/docs/third-party-plugins/fullcalendar/)
## ---

**9. Ek Araştırma Bulguları (Ocak 2026)**

Yapılan ek teknik araştırmalar sonucunda, entegrasyon planını etkileyecek kritik detaylar netleşmiştir.

### **9.1. Görünüm Modları ve "Ajanda" Eksikliği**
TOAST UI Calendar yerleşik olarak bir **Ajanda (List)** görünümü sunmamaktadır. Sadece `month`, `week` ve `day` görünümleri mevcuttur.
*   **Çözüm:** Ajanda görünümü için TOAST UI zorlanmamalıdır. Bunun yerine, aynı veriyi kullanan ayrı bir **Livewire/Volt** bileşeni (HTML/Tailwind listesi) oluşturulmalıdır. Bu, hem mobil deneyimi iyileştirir hem de tasarım esnekliği sağlar.

### **9.2. Mobil ve Responsive Davranış**
Kütüphane touch event'leri (sürükle-bırak) desteklemektedir ve `mousedown` olaylarını otomatik olarak `touchstart`'a çevirir. Ancak:
*   **Masaüstü Öncelikli:** Varsayılan stiller masaüstü odaklıdır. Mobilde grid hücrelerinin okunabilirliği düşmektedir.
*   **Öneri:** Mobil cihazlarda varsayılan olarak **Günlük (Day)** görünümü veya yukarıda bahsedilen **Ajanda** bileşeni gösterilmelidir. CSS ile `.toastui-calendar-grid-cell` yükseklikleri mobilde artırılmalıdır.

### **9.3. Türkçe Yerelleştirme (i18n)**
Tam Türkçe desteği mümkündür.
*   **Kurulum:** `startDayOfWeek: 1` (Pazartesi) ayarına ek olarak, `month.daynames` ve `week.daynames` dizileri Türkçe olarak konfigürasyona geçilmelidir.
*   **Template:** Kütüphanenin standart metinleri (örn: "milestone") `template` seçeneği ile Türkçeleştirilebilir.

### **9.4. Modern Kurulum (NPM + Vite)**
2026 standartlarında CDN yerine NPM kurulumu en sağlıklı yöntemdir.
*   **Paket:** `npm install @toast-ui/calendar`
*   **Vite:** `resources/js/calendar.js` içinde import edilip, `window` nesnesine atanarak Alpine.js tarafından erişilebilir hale getirilmelidir.
*   **Style:** CSS dosyası `import '@toast-ui/calendar/dist/toastui-calendar.min.css';` ile dahil edilmelidir.

Bu bulgular ışığında, **Faz 4** planlamasında Ajanda görünümü için ayrı bir efor ayrılmalı ve mobil stratejisi buna göre kurgulanmalıdır.
