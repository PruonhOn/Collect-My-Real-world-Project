<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class PruonhPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'Education'],
            ['slug' => 'education-'.substr(md5('Education'), 0, 6), 'description' => 'ការអប់រំ ➜ Textbooks and learning materials.']
        );

        Book::updateOrCreate(
            ['slug' => 'pruonh-portfolio'],
            [
                'category_id' => $category->id,
                'title' => 'Pruonh Portfolio',
                'description' => 'A personal portfolio showcasing skills, projects, experience and achievements presented as a 10-page interactive book.',
                'author' => 'Pruonh',
                'content' => implode("\n\n==========\n\n", [
                    $this->aboutPage(),
                    $this->skillsPage(),
                    $this->projectsPage(),
                    $this->experiencePage(),
                    $this->educationPage(),
                    $this->achievementsPage(),
                    $this->certificationsPage(),
                    $this->testimonialsPage(),
                    $this->contactPage(),
                    $this->summaryPage(),
                ]),
                'file_path' => null,
                'file_type' => 'text',
                'file_size' => 0,
                'language' => 'en',
                'pages' => 10,
                'price' => 0,
                'is_free' => true,
                'is_published' => true,
            ]
        );

        $this->command?->info('Pruonh Portfolio book created with 10 pages.');
    }

    private function aboutPage(): string
    {
        return "Page 1 - About Me\n\n"
            .'Hello! My name is Pruonh and this portfolio introduces my journey, work and passion. '
            .'I am a creative and driven individual who loves learning new technologies, solving real problems, '
            .'and building useful things for the community around me. This 10-page book walks you through who I am, '
            .'the skills I have developed, the projects I have completed, my work experience, education, achievements, '
            .'certifications, what people say about working with me, and how you can reach out. '
            .'I believe that every challenge is an opportunity to grow, and I am always eager to take on new ones.';
    }

    private function skillsPage(): string
    {
        return "Page 2 - My Skills\n\n"
            .'I have worked with a variety of tools and technologies over the years. On the technical side, I am comfortable '
            .'building web applications with HTML, CSS, JavaScript and modern frameworks, and I enjoy working with backend '
            .'systems that make data flow smoothly. I also know how to design clean, user-friendly interfaces that people '
            .'actually enjoy using. Beyond technology, I have strong communication, teamwork, time management and problem-solving '
            .'skills that help me work effectively in any environment. I am a fast learner who picks up new tools quickly '
            .'and adapts well to changing requirements.';
    }

    private function projectsPage(): string
    {
        return "Page 3 - Featured Projects\n\n"
            .'One of my proudest projects is a digital e-book platform that lets users browse, read and buy books online '
            .'with a smooth, page-by-page reading experience. I designed the user interface, built the browsing and search '
            .'features, and connected it to a reliable backend. I have also built small portfolio websites, simple management '
            .'systems, and interactive learning tools. Each project taught me something valuable about planning, coding, testing '
            .'and shipping software that people can depend on. I always document my work so that others can learn from it too.';
    }

    private function experiencePage(): string
    {
        return "Page 4 - Work Experience\n\n"
            .'I have gained hands-on experience through internships, freelance work and collaborative team projects. '
            .'During my internship at a software company, I helped maintain frontend features, fixed bugs, and worked closely '
            .'with senior developers to ship updates on schedule. I also took responsibilities in coordinating small teams, '
            .'writing technical reports, and presenting progress to stakeholders. These experiences taught me how to '
            .'communicate clearly, manage my time, and deliver quality work even under pressure. I am now looking for '
            .'opportunities where I can continue to grow and contribute meaningfully.';
    }

    private function educationPage(): string
    {
        return "Page 5 - Education\n\n"
            .'My education has given me a strong foundation for my career. I studied information technology and computer '
            .'science, where I learned programming fundamentals, data structures, databases and software development '
            .'methodologies. In addition to my formal studies, I have completed countless hours of self-learning through '
            .'online courses, tutorials and personal projects. I believe education never really stops, so I continuously '
            .'update my knowledge by reading, building small experiments and following industry trends. My goal is always '
            .'to turn what I learn into practical skills that create value for others.';
    }

    private function achievementsPage(): string
    {
        return "Page 6 - Achievements\n\n"
            .'Over the years I have earned several achievements that I am proud of. I have completed team projects that '
            .'were recognized by instructors, won small competitions in software design, and successfully delivered '
            .'freelance work that satisfied my clients. I also led study groups where I helped classmates understand '
            .'difficult programming concepts, which strengthened my communication and teaching abilities. Each achievement '
            .'reminds me that consistent effort and a positive attitude really pay off. I keep a learning journal where '
            .'I record my progress and the lessons I learn from every milestone.';
    }

    private function certificationsPage(): string
    {
        return "Page 7 - Certifications\n\n"
            .'I have earned certifications that validate my skills and knowledge. I completed courses in web development, '
            .'responsive design and backend programming, and received certificates after passing the final assessments. '
            .'These certifications taught me both theory and practice, and I make sure to keep them up to date as the '
            .'industry evolves. I also participate in workshops and seminars about modern development tools and best '
            .'practices. Certifications are not just pieces of paper to me; they represent hours of focused study and '
            .'the commitment to keep growing professionally.';
    }

    private function testimonialsPage(): string
    {
        return "Page 8 - Testimonials\n\n"
            .'People who have worked with me often describe me as reliable, creative and easy to work with. My teammates '
            .'appreciate how I communicate clearly and step in to help whenever someone is stuck. My clients value the '
            .'attention to detail I bring to every task and the way I keep them updated on progress. My teachers have '
            .'noticed my curiosity and my willingness to go beyond what is required. I take these comments seriously and '
            .'use them to improve how I collaborate and deliver results. Their trust motivates me to keep raising the bar.';
    }

    private function contactPage(): string
    {
        return "Page 9 - Contact Me\n\n"
            .'I would love to hear from you! Whether you have a project in mind, an opportunity to share, or simply want '
            .'to connect and chat about technology and education, feel free to reach out. You can send me a message through '
            .'this platform or find me on my social profiles, and I will get back to you as soon as possible. I am '
            .'particularly interested in internships, junior roles, and collaborative projects where I can contribute and '
            .'learn. Please include a short introduction about yourself and what you would like to talk about so I can '
            .'prepare the most helpful response.';
    }

    private function summaryPage(): string
    {
        return "Page 10 - Summary & Thank You\n\n"
            .'Thank you for taking the time to read my portfolio. In these ten pages you have seen who I am, what I can do, '
            .'what I have accomplished, and what I hope to achieve next. I am a curious, hard-working and friendly person '
            .'who is ready to learn and contribute. If you see a way we could work together, I would be delighted to discuss '
            .'it further. I look forward to hearing from you and to the possibility of collaborating on something great. '
            .'Once again, thank you for your time and interest, and I hope to connect with you soon!';
    }
}
