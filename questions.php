<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Generated Questions</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: { 
        extend: { 
          fontFamily: { sans: ['Inter', 'system-ui'] },
          boxShadow: {
            'xl': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            '2xl': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
          },
          colors: {
            primary: '#6366f1',
            secondary: '#ec4899',
            accent: '#fbbf24',
          }
        }
      }
    }
  </script>
  <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
</head>
<body class="h-full flex items-center justify-center p-6">
<div class="max-w-2xl w-full" x-data="quiz()" x-init="init()">
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 border-t-4 border-secondary">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Generated Questions</h1>
      <button @click="toggleTheme" class="text-2xl" x-text="theme === 'light' ? '🌙' : '☀️'"></button>
    </div>

    <div x-show="questions.length > 0" class="mt-10">
      <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 text-center">Quiz Time!</h2>
      <div class="mb-4 text-center">
        <button @click="shuffleQuestions()" class="py-2 px-4 bg-accent text-white rounded-lg hover:opacity-90 transition">Shuffle Questions</button>
      </div>

      <div class="bg-blue-50 dark:bg-gray-700 rounded-xl shadow-xl p-6 mb-6">
        <div class="text-center mb-4 text-gray-500 dark:text-gray-400 font-semibold" x-text="(currentIndex + 1) + ' / ' + questions.length"></div>
        <p class="text-lg font-medium text-primary dark:text-indigo-300 mb-4" x-text="questions[currentIndex].question"></p>
        <div class="flex items-center mb-2">
          <input type="text" x-model="questions[currentIndex].userAnswer" class="flex-grow px-4 py-2 border border-gray-300 dark:border-gray-500 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-secondary transition" placeholder="Type your answer...">
          <button @click="questions[currentIndex].show = true" class="ml-4 text-sm text-secondary dark:text-pink-400 hover:underline">Show Answer</button>
        </div>
        <span x-show="questions[currentIndex].show" class="block mt-2 text-accent dark:text-yellow-400 font-bold" x-text="questions[currentIndex].answer"></span>
        <div class="mt-2 text-center">
          <button @click="submitAnswer()" class="py-1 px-3 bg-primary text-white rounded-lg hover:opacity-90 transition text-sm">Submit Answer</button>
        </div>
        <div x-show="questions[currentIndex].submitted" class="mt-2 text-center font-semibold" :class="questions[currentIndex].isCorrect ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="questions[currentIndex].isCorrect ? 'Correct ✅' : 'Wrong ❌'"></div>
      </div>

      <div class="flex justify-between">
        <button @click="prev" :disabled="currentIndex === 0" class="py-2 px-4 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg disabled:opacity-50 hover:bg-gray-300 dark:hover:bg-gray-500 transition">Previous</button>
        <button @click="next" :disabled="currentIndex === questions.length - 1" class="py-2 px-4 bg-primary text-white rounded-lg disabled:opacity-50 hover:opacity-90 transition">Next</button>
      </div>

      <div class="mt-6 text-center">
        <div class="mb-2 font-semibold text-gray-700 dark:text-gray-300" x-text="'Score: ' + score + ' / ' + questions.length"></div>
        <button @click="clearResults" class="text-sm text-primary dark:text-indigo-400 hover:underline">Clear Results & Return</button>
      </div>
    </div>

    <div x-show="questions.length === 0" class="mt-10 text-center text-gray-600 dark:text-gray-400">
      <p>No questions found. Please generate questions first.</p>
      <a href="index.php" class="mt-4 inline-block text-primary dark:text-indigo-400 hover:underline">Back to Generator</a>
    </div>
  </div>
</div>

<script>
function quiz() {
  return {
    questions: [],
    currentIndex: 0,
    score: 0,
    theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),

    init() {
      document.documentElement.classList.toggle('dark', this.theme === 'dark');
      const stored = localStorage.getItem('generatedQuestions');
      if (stored) {
        this.questions = JSON.parse(stored);
        // Initialize submission flags
        this.questions.forEach(q => { q.submitted = false; q.isCorrect = false; });
      }
    },

    toggleTheme() {
      this.theme = this.theme === 'light' ? 'dark' : 'light';
      localStorage.setItem('theme', this.theme);
      document.documentElement.classList.toggle('dark', this.theme === 'dark');
    },

    prev() {
      if (this.currentIndex > 0) this.currentIndex--;
    },

    next() {
      if (this.currentIndex < this.questions.length - 1) this.currentIndex++;
    },

    submitAnswer() {
      const q = this.questions[this.currentIndex];
      if (!q.submitted) {
        q.submitted = true;
        q.isCorrect = q.userAnswer.trim().toLowerCase() === q.answer.trim().toLowerCase();
        if (q.isCorrect) this.score++;
      }
    },

    shuffleQuestions() {
      for (let i = this.questions.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [this.questions[i], this.questions[j]] = [this.questions[j], this.questions[i]];
      }
      this.currentIndex = 0;
      this.score = 0;
      this.questions.forEach(q => { q.submitted = false; q.isCorrect = false; });
    },

    clearResults() {
      localStorage.removeItem('generatedQuestions');
      window.location.href = 'index.php';
    }
  }
}
</script>
</body>
</html>
