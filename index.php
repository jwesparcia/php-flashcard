<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50 dark:bg-gray-900">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PDF to Literal Questions</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'system-ui']
          },
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
  <div class="max-w-2xl w-full" x-data="app()" x-init="init()">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 border-t-4 border-primary">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">PDF → Exact Questions</h1>
        <button @click="toggleTheme" class="text-2xl" x-text="theme === 'light' ? '🌙' : '☀️'"></button>
      </div>
      <p class="text-gray-600 dark:text-gray-400 mb-8">Generate questions using <strong>only the exact wording</strong> from your notes.</p>

      <form @submit.prevent="upload()" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload PDF Notes</label>
          <input type="file" accept=".pdf" @change="file = $event.target.files[0]" required
            class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-3 file:px-6 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:opacity-90 transition" />
          <p x-show="!file && submitted" class="text-red-600 dark:text-red-400 text-sm mt-1">Please select a PDF file.</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Number of Questions</label>
          <input type="number" x-model="numQuestions" min="5" max="50" value="15"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 transition" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your AI API Key (Gemini or OpenAI)</label>
          <input type="password" x-model="apiKey" placeholder="sk-... or gemini-..." required
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 transition" />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Your key is never stored. Processed in-browser.
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Don’t have an OpenAI API key? <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary dark:text-indigo-400 hover:underline">Create one here</a>:
          <ol class="list-decimal ml-4 mt-1 text-gray-700 dark:text-gray-100">
            <li>Click the link above to go to the OpenAI API Keys page.</li>
            <li>Click <strong>"Create new secret key"</strong>.</li>
            <li>Click <strong>"Create secret key"</strong> in the popup.</li>
            <li>Copy the generated key and paste it above.</li>
          </ol>
          </p>


        </div>

        <button type="submit" :disabled="loading"
          class="w-full py-4 px-6 bg-primary hover:opacity-90 disabled:bg-gray-400 text-white font-semibold rounded-lg transition">
          <span x-show="!loading">Generate Questions</span>
          <span x-show="loading">Processing PDF & Generating...</span>
        </button>
      </form>

      <div x-show="error" class="mt-6 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-300" x-text="error"></div>
    </div>
  </div>

  <script>
    function app() {
      return {
        file: null,
        numQuestions: 15,
        apiKey: localStorage.getItem('lastApiKey') || '',
        loading: false,
        error: '',
        submitted: false,
        theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),

        init() {
          document.documentElement.classList.toggle('dark', this.theme === 'dark');
        },

        toggleTheme() {
          this.theme = this.theme === 'light' ? 'dark' : 'light';
          localStorage.setItem('theme', this.theme);
          document.documentElement.classList.toggle('dark', this.theme === 'dark');
        },

        upload() {
          this.submitted = true;
          if (!this.file || !this.apiKey) return;

          this.loading = true;
          this.error = '';

          const formData = new FormData();
          formData.append('pdf', this.file);
          formData.append('num', this.numQuestions);

          localStorage.setItem('lastApiKey', this.apiKey);

          fetch('process.php', {
              method: 'POST',
              body: formData
            })
            .then(r => {
              if (!r.ok) {
                throw new Error(`HTTP ${r.status}: ${r.statusText}`);
              }
              return r.text().then(text => {
                console.log('Raw server response:', text);
                if (text.trim() === '') {
                  throw new Error('Empty response from server');
                }
                const parsed = JSON.parse(text);
                if (parsed.error) throw new Error(parsed.error);
                return parsed;
              });
            })
            .then(data => {
              this.callAI(data.text);
            })
            .catch(err => {
              console.error('Full error:', err);
              this.error = err.message || 'Failed to extract text from PDF.';
              this.loading = false;
            });
        },

        callAI(pdfText) {
          const prompt = `You are a strict question generator. Your task is to generate exactly ${this.numQuestions} fill-in-the-blank questions using ONLY the exact sentences and phrases from the following text.

RULES (MUST FOLLOW STRICTLY):
- Identify declarative sentences that define a term, like "Term is definition."
- Convert them to "_____ [exact definition starting from 'is' or equivalent]" where the blank is for the term and the definition is the question part.
- Do NOT paraphrase, reword, summarize, interpret, or add any words not in the text.
- Use only direct quotes or sentence fragments from the text.
- Example input sentence: "Software testing is the activity of executing a system or component under specified conditions, observing or recording the results, and evaluating some of the aspects of the system or component."
- Example output format for that: _____ is the activity of executing a system or component under specified conditions, observing or recording the results, and evaluating some of the aspects of the system or component. | Software testing
- If no suitable sentences exist, use "According to the text, [exact phrase]?" but prefer definition blanks.
- Aim for diversity; don't repeat similar questions.

TEXT:
${pdfText}

Generate exactly ${this.numQuestions} questions, numbered 1 to ${this.numQuestions}. Each in format: [blank question] | [answer term]
Only output the list. No extra text.`;

          fetch('https://api.openai.com/v1/chat/completions', {
              method: 'POST',
              headers: {
                'Authorization': 'Bearer ' + this.apiKey,
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                model: 'gpt-4o-mini',
                messages: [{
                  role: 'user',
                  content: prompt
                }],
                temperature: 0.3,
                max_tokens: 2000
              })
            })
            .then(r => r.json())
            .then(res => {
              if (res.error) throw new Error(res.error.message);
              const text = res.choices[0].message.content.trim();
              const lines = text.split('\n').filter(Boolean);
              const questions = lines.map(l => {
                let clean = l.replace(/^\d+\.\s*/, '').trim();
                let [question, answer] = clean.split(' | ').map(s => s.trim());
                return {
                  question,
                  answer,
                  userAnswer: '',
                  show: false
                };
              });
              localStorage.setItem('generatedQuestions', JSON.stringify(questions));
              window.location.href = 'questions.php';
            })
            .catch(err => {
              this.error = 'AI API Error: ' + (err.message || 'Invalid API key or rate limit.');
            })
            .finally(() => {
              this.loading = false;
            });
        }
      }
    }
  </script>
</body>

</html>