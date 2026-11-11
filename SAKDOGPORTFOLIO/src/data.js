// ============================================================================
//  ALL SITE CONTENT — edit your details here.
//  Khmer text (Km) is the primary language, English (En) gives the bilingual feel.
//  TODO: replace the placeholder name, school, email and social URLs.
// ============================================================================

export const profile = {
  name: 'Sak',
  greetingKm: 'សួស្តី! ខ្ញុំឈ្មោះ',
  roleEn: 'Software Engineer · Full-stack Developer',
  roleKm: 'វិស្វករផ្នែកសូហ្វវែរ · អ្នកអភិវឌ្ឍន៍ Full-stack',
  taglineKm:
    'ខ្ញុំបង្កើតប្រព័ន្ធវេបដែលលឿន ស្អាត និងមានអត្ថន័យ ដើម្បីបម្រើដល់អ្នកប្រើប្រាស់គ្រប់រូប។',
  taglineEn: 'I build fast, clean, and meaningful web systems for real people.',
  email: 'hello@sak.dev',
  telegram: 'https://t.me/yourhandle',
  github: 'https://github.com/yourhandle',
  linkedin: 'https://www.linkedin.com/in/yourhandle',
}

export const navLinks = [
  { id: 'about', labelKm: 'អំពីខ្ញុំ', labelEn: 'About' },
  { id: 'skills', labelKm: 'ជំនាញ', labelEn: 'Skills' },
  { id: 'projects', labelKm: 'គម្រោង', labelEn: 'Projects' },
  { id: 'experience', labelKm: 'បទពិសោធន៍', labelEn: 'Experience' },
  { id: 'testimonials', labelKm: 'ពាក្យសម្តី', labelEn: 'Testimonials' },
  { id: 'contact', labelKm: 'ទាក់ទង', labelEn: 'Contact' },
]

export const techBadges = [
  { name: 'React', color: '#61dafb' },
  { name: 'PHP', color: '#8892bf' },
  { name: 'JavaScript', color: '#f7df1e' },
  { name: 'Python', color: '#4b8bbe' },
  { name: 'Cisco CCNA', color: '#049fd9' },
]

export const about = {
  // TODO: replace the school with the real one.
  schoolKm: 'សាកលវិទ្យាល័យភ្នំពេញ',
  schoolEn: 'Royal University of Phnom Penh',
  bioKm: [
    'ខ្ញុំជានិស្សិតឆ្នាំទី៣ អាយុ ២១ឆ្នាំ ផ្នែកបច្ចេកវិទ្យាព័ត៌មាន (IT) នៅសាកលវិទ្យាល័យភ្នំពេញ។ អ្វីដែលជំរុញឱ្យខ្ញុំចូលចិត្តសរសេរកូដ គឺការចង់ដឹងថាតើគេហទំព័រនីមួយៗដំណើរការដោយរបៀបណា — ពី HTML/CSS/JavaScript រហូតដល់ React និង PHP ដើម្បីបង្កើតប្រព័ន្ធពេញលេញ។',
    'បន្ថែមពីលើការអភិវឌ្ឍវេប ខ្ញុំក៏បានសិក្សាផ្នែកបណ្តាញកុំព្យូទ័រ (Networking) ជាមួយ Cisco CCNA 1–3 ដែលធ្វើឱ្យខ្ញុំយល់ពីប្រព័ន្ធទាំងមូលពី Frontend រហូតដល់ហេដ្ឋារចនាសម្ព័ន្ធ។',
  ],
  bioEn:
    "A 3rd-year IT student who fell in love with code through a simple question: “how do websites actually work?” That curiosity grew from HTML/CSS/JS into React and PHP, plus networking fundamentals through Cisco CCNA 1–3.",
  stats: [
    { value: '21', labelKm: 'អាយុ', labelEn: 'Age' },
    { value: '3', labelKm: 'ឆ្នាំរៀន IT', labelEn: 'Years in IT' },
    { value: '3', labelKm: 'វគ្គ CCNA', labelEn: 'CCNA Courses' },
    { value: '8+', labelKm: 'គម្រោងរួចរាល់', labelEn: 'Projects Done' },
  ],
  features: [
    {
      icon: 'code',
      titleKm: 'កូដស្អាត',
      titleEn: 'Clean Code',
      descKm: 'កូដច្បាស់ អានងាយ និងងាយស្រួលថែទាំ',
      descEn: 'Readable, structured, and maintainable.',
    },
    {
      icon: 'eye',
      titleKm: 'រចនាផ្តោតលើអ្នកប្រើ',
      titleEn: 'User-Focused Design',
      descKm: 'គិតពីបទពិសោធន៍អ្នកប្រើជាចំបង',
      descEn: 'Experience-first thinking in every screen.',
    },
    {
      icon: 'refresh',
      titleKm: 'រៀនសូត្រជាបន្ត',
      titleEn: 'Continuous Learning',
      descKm: 'ស្វែងយល់បច្ចេកវិទ្យាថ្មីៗជានិច្ច',
      descEn: 'Always exploring what’s next.',
    },
    {
      icon: 'briefcase',
      titleKm: 'ទទួលខុសត្រូវ',
      titleEn: 'Ownership & Responsibility',
      descKm: 'ទទួលខុសត្រូវលើការងារពីចុងដល់ចុង',
      descEn: 'Own tasks from start to finish.',
    },
  ],
}

export const skillGroups = [
  {
    titleKm: 'ផ្នែកខាងមុខ',
    titleEn: 'Frontend',
    icon: 'layers',
    accent: '#818cf8',
    skills: [
      { name: 'HTML / CSS', level: 90 },
      { name: 'JavaScript', level: 85 },
      { name: 'React', level: 80 },
      { name: 'Tailwind CSS', level: 85 },
    ],
  },
  {
    titleKm: 'ផ្នែកខាងក្រោយ',
    titleEn: 'Backend',
    icon: 'database',
    accent: '#22d3ee',
    skills: [
      { name: 'PHP', level: 80 },
      { name: 'Laravel', level: 70 },
      { name: 'MySQL', level: 75 },
      { name: 'REST API', level: 70 },
    ],
  },
  {
    titleKm: 'បណ្តាញកុំព្យូទ័រ',
    titleEn: 'Networking',
    icon: 'wifi',
    accent: '#34d399',
    skills: [
      { name: 'Cisco CCNA', level: 75 },
      { name: 'Routing & Switching', level: 80 },
      { name: 'Network Security', level: 65 },
      { name: 'TCP/IP', level: 85 },
    ],
  },
  {
    titleKm: 'ឧបករណ៍ និងជំនាញទន់',
    titleEn: 'Tools & Soft Skills',
    icon: 'wrench',
    accent: '#fbbf24',
    skills: [
      { name: 'Git & GitHub', level: 85 },
      { name: 'VS Code', level: 90 },
      { name: 'Teamwork', level: 90 },
      { name: 'Problem Solving', level: 88 },
    ],
  },
]

export const projectCategories = [
  { id: 'all', labelKm: 'ទាំងអស់', labelEn: 'All' },
  { id: 'management', labelKm: 'ប្រព័ន្ធគ្រប់គ្រង', labelEn: 'Management Systems' },
  { id: 'networking', labelKm: 'បណ្តាញ', labelEn: 'Networking' },
]

export const projects = [
  {
    id: 1,
    category: 'management',
    titleKm: 'ប្រព័ន្ធគ្រប់គ្រងហាងទំនិញតូច',
    titleEn: 'Mini-Mart Management System',
    descKm:
      'គ្រប់គ្រងស្តុកទំនិញ ការលក់ និងរបាយការណ៍ក្នុងផ្ទាំងគ្រប់គ្រងដ៏ស្អាតមួយ។',
    descEn: 'Inventory, sales, and reporting in one clean dashboard.',
    tags: ['React', 'PHP', 'MySQL'],
    gradient: 'from-indigo-500 via-violet-500 to-purple-500',
    initial: 'MM',
  },
  {
    id: 2,
    category: 'management',
    titleKm: 'ប្រព័ន្ធគ្រប់គ្រងកាតស្គាល់សិស្ស',
    titleEn: 'Student ID Card System',
    descKm: 'ផលិតកាតសិស្សឌីជីថល ជាមួយការថតរូប និងការបោះពុម្ពដោយស្វ័យប្រវត្តិ។',
    descEn: 'Digital student identity cards with photo capture and print-ready output.',
    tags: ['React', 'Node.js', 'MySQL'],
    gradient: 'from-cyan-500 via-sky-500 to-blue-500',
    initial: 'ID',
  },
  {
    id: 3,
    category: 'management',
    titleKm: 'ប្រព័ន្ធគ្រប់គ្រងធនាគារ',
    titleEn: 'Banking Management System',
    descKm: 'គ្រប់គ្រងគណនី ប្រតិបត្តិការ និងសិទ្ធិចូលប្រើតាមតួនាទីប្រកបដោយសុវត្ថិភាព។',
    descEn: 'Accounts, transactions, and secure role-based access.',
    tags: ['PHP', 'MySQL', 'JavaScript'],
    gradient: 'from-emerald-500 via-teal-500 to-cyan-500',
    initial: 'BK',
  },
  {
    id: 4,
    category: 'networking',
    titleKm: 'គម្រោងរចនាបណ្តាញ Cisco',
    titleEn: 'Cisco Network Design',
    descKm: 'រចនាបណ្តាញច្រើន VLAN ជាមួយ DHCP, Routing និងសុវត្ថិភាពបណ្តាញ។',
    descEn: 'A multi-VLAN network with DHCP, routing, and security best practices.',
    tags: ['Cisco', 'Packet Tracer', 'CCNA'],
    gradient: 'from-slate-500 via-slate-700 to-slate-900',
    initial: 'NW',
  },
]

export const experience = [
  {
    period: '2023 — Present',
    titleKm: 'និស្សិតឆ្នាំទី៣ បច្ចេកវិទ្យាព័ត៌មាន',
    titleEn: 'B.Sc. IT — 3rd Year',
    orgKm: 'សាកលវិទ្យាល័យភ្នំពេញ',
    orgEn: 'Royal University of Phnom Penh',
    bulletsKm: ['សិក្សាផ្នែកអភិវឌ្ឍវេប មូលដ្ឋានទិន្នន័យ និងវិស្វកម្មសូហ្វវែរ', 'បង្កើតគម្រោងជាក់ស្តែងជាមួយ React និង PHP'],
    bulletsEn: ['Studying web dev, databases & software engineering', 'Building hands-on projects with React and PHP'],
  },
  {
    period: '2024 — Present',
    titleKm: 'វិញ្ញាបនបត្រ Cisco CCNA',
    titleEn: 'Cisco CCNA Certification Track',
    orgKm: 'Cisco Networking Academy',
    orgEn: 'Cisco Networking Academy',
    bulletsKm: ['បញ្ចប់ CCNA 1–3: Routing, Switching, Subnetting និងសុវត្ថិភាព', 'រចនាបណ្តាញ LAN/WAN ក្នុង Packet Tracer'],
    bulletsEn: ['Completed CCNA 1–3: routing, switching, subnetting & security', 'Designed LAN/WAN topologies in Packet Tracer'],
  },
  {
    period: '2025',
    titleKm: 'គម្រោង Full-stack ឯករាជ្យ',
    titleEn: 'Independent Full-stack Project',
    orgKm: 'គម្រោងផ្ទាល់ខ្លួន',
    orgEn: 'Self-directed',
    bulletsKm: ['រចនា និងបង្កើតប្រព័ន្ធគ្រប់គ្រងហាងតូចពេញលេញពីដើមដល់ចប់', 'រៀបចំមូលដ្ឋានទិន្នន័យ បង្កើត REST API និង UI ដែលឆ្លើយតបល្អ'],
    bulletsEn: ['Designed & built a full mini-mart web app end-to-end', 'Planned the database, built the REST API, shipped a responsive UI'],
  },
]

export const testimonials = [
  {
    quoteKm:
      'ជានិស្សិតដែលយកចិត្តទុកដាក់បំផុតម្នាក់ — កូដតែងតែស្អាត ហើយតែងតែធ្វើការងារឱ្យចប់សព្វគ្រប់។',
    quoteEn:
      'One of the most dedicated students I’ve taught — the code is always clean and the work always gets finished.',
    name: 'Dr. Chan Sokha',
    roleKm: 'សាស្ត្រាចារ្យ អភិវឌ្ឍវេប',
    roleEn: 'Lecturer · Web Development',
  },
  {
    quoteKm:
      'មិត្តរួមក្រុមដែលអាចទុកចិត្តបាន — ទំនាក់ទំនងច្បាស់លាស់ និងបញ្ជូនការងារទាន់ពេលជានិច្ច។',
    quoteEn:
      'A reliable teammate who communicates clearly and always delivers on time.',
    name: 'Srey Leak',
    roleKm: 'មិត្តរួមក្រុម · គម្រោងក្រុម',
    roleEn: 'Teammate · Group Project',
  },
  {
    quoteKm:
      'រៀនគំនិតបណ្តាញបានលឿន ហើយប្រុងប្រយ័ត្នលើការកំណត់រចនាសម្ព័ន្ធណាស់។',
    quoteEn:
      'Quick to grasp networking concepts and very careful with configurations.',
    name: 'Vutha Rin',
    roleKm: 'អ្នកបច្ចេកទេសបណ្តាញជាន់ខ្ពស់',
    roleEn: 'Senior Network Technician',
  },
]

export const footerNoteKm = 'បង្កើតឡើងដោយក្តីស្រឡាញ់ ជាមួយ React និង Tailwind CSS'
export const footerNoteEn = 'Built with love using React & Tailwind CSS'
