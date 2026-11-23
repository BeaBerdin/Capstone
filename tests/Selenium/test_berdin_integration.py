"""Berdin, Bea - Selenium integration tests (Appendix: Integration Testing).

Each test is one documented case (IntegTestCase-01 ... IntegTestCase-15). Every
case goes through two connected modules in the browser: the action in
Module 1 must show its effect in Module 2. At least one of the two modules is
one of Berdin's modules in the List of Modules (Table 37).

The data comes from tests/Selenium/support/runtime.php on a disposable SQLite
database. Gemini is replaced by local test doubles (no call to the real API).

Each test saves two screenshots in artifacts/integration/:
<test name>_fig1.png (the action in Module 1) and <test name>_fig2.png
(the result shown in Module 2).
"""

import time

import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select

from conftest import ARTIFACTS, fixture as F, login, page_text, url

# Case metadata for the Integration Testing document and the Chapter III summary table.
# (module 1, integration process, module 2, pre-condition, expected result, simulated service)
CASES = {
    1: ("Submit Course for Approval", "The teacher submits a draft course; it appears in the department administrator's course list as Pending with Approve and Return for Revision.", "Review/Approve/Reject Courses",
        "A teacher has a draft course with a published lesson. The administrator (Department Head) is in the same department.",
        "After the teacher clicks Submit for Approval, the course is shown as Pending in the administrator's Courses page with Approve and Return for Revision buttons.", None),
    2: ("Review/Approve/Reject Courses", "The administrator approves a pending course; it becomes visible to students in the Course Marketplace.", "Browse Course Marketplace",
        "A course is pending approval. A student is signed in.",
        "After the administrator clicks Approve, the course is published and listed in the student's Course Marketplace.", None),
    3: ("Manage Course Categories", "The Super Admin (EDP) adds a course category; the teacher can choose it when creating a course.", "Create Course",
        "The Super Admin (EDP) and a teacher are signed in. The category does not exist yet.",
        "The new category is saved and appears in the Category dropdown of the teacher's Create Course page.", None),
    4: ("Preview Lesson before Enrolling", "A student who is not enrolled previews a lesson and enrolls from the preview banner; the full lesson opens with Mark as Complete.", "Study Lessons",
        "A published free course has a published Reading lesson marked as a preview. The student is not enrolled.",
        "The preview is read-only with the enroll banner; after Enroll for Free the student opens the normal lesson page with Mark as Complete, and the course is in My Courses.", None),
    5: ("Study Lessons", "The student marks a lesson as complete; the course progress on the learning page increases.", "View Progress",
        "The student is enrolled in a course with 4 published lessons and has completed 1 (25%).",
        "After Mark as Complete, the learning page shows 2 of 4 lessons and 50% complete.", None),
    6: ("AI Quiz Question Generation (Gemini)", "The teacher generates quiz questions with Gemini and publishes the quiz; after the course is approved, an enrolled student takes it and gets a score.", "Take Quiz",
        "A returned course has a published Reading lesson and an empty quiz lesson. Gemini is simulated locally.",
        "Gemini questions are added to the quiz, the quiz is published, and the student who enrolls after approval takes it and sees Latest score: 100.0%.", "Gemini simulated locally"),
    7: ("Compute Quiz Score", "A failing score is computed and the student is offered a retake; the retake replaces the result and the quiz is passed.", "Retake Quiz",
        "The student is enrolled in a course with a published quiz (passing score 75%) and has no attempt yet.",
        "The first attempt shows Latest score: 50.0% and Needs Retake; after Retake Quiz with all correct answers the page shows Latest score: 100.0% and Quiz Passed.", None),
    8: ("Take Quiz", "The student submits a quiz; the attempt and score appear in the administrator's Quiz Results.", "View Quiz Results",
        "The student is enrolled in a course with a published quiz. The administrator manages that course's department.",
        "The student sees Latest score: 100.0%, and the administrator's Quiz Results lists the student's attempt with 100%.", None),
    9: ("Create Assignment (Essay, Code, Multiple Choice)", "The teacher creates and publishes an assignment; an enrolled student sees it and submits an answer.", "Submit Assignment",
        "The teacher owns a published course with an enrolled student.",
        "The assignment is created, it is listed on the student's Assignments page, and the student's answer is accepted.", None),
    10: ("AI Evaluation and Grading of Submissions", "The teacher saves a final grade below 75%; the student sees the grade and can resubmit the activity.", "Activity Lessons",
         "The student submitted an activity (maximum score 100) in an activity lesson.",
         "The grade 60/100 is saved; the student sees Final score 60 and the activity form is offered again for resubmission.", None),
    11: ("AI Evaluation and Grading of Submissions", "The student completes the lessons and submits the final activity; when the teacher saves a passing final grade, the certificate is issued.", "Generate Certificate",
         "The student is enrolled in a course that offers a certificate and has one Reading lesson and one activity.",
         "Before grading the course page shows the certificate as locked with the reason \"Waiting for your teacher to grade: Final Project\"; after a passing grade (90) the certificate is listed in My Certificates.", None),
    12: ("Take Quiz", "A quiz result produces a course recommendation with a Gemini explanation on the student's AI Recommendations page.", "AI Recommendation Engine",
         "The student is enrolled in a course with a published quiz and has no quiz result yet. Gemini is simulated locally.",
         "After the quiz (100%), the AI Recommendations page shows a recommended course with the explanation from Gemini.", "Gemini simulated locally"),
    13: ("Manage AI Recommendations", "The administrator generates a recommendation for a student from the student's quiz result; the student sees it on the AI Recommendations page.", "AI Recommendation Engine",
         "The student has a published quiz result (90%).",
         "The recommendation for Advanced Algorithms is created and shown on the student's AI Recommendations page with the explanation for a strong quiz result and the 90% score.", None),
    14: ("Request Student Removal", "The teacher requests to remove a student; the Department Head sees the request, approves it, and the student loses access.", "Review Student Removal Requests",
         "The student is enrolled in the teacher's course. The Department Head manages the teacher's department.",
         "The request appears as Pending in Student Removal Requests; after approval the student can no longer open the course (403).", None),
    15: ("Moderate Course Reviews", "The administrator hides a review with a reason; the review disappears from the public course page.", "Course Reviews (write / view)",
         "A course has a visible review that breaks the guidelines and another visible review.",
         "The review is hidden in Course Reviews and is no longer shown on the course page, while the other review stays visible.", None),
}


# ---------------------------------------------------------------- helpers

def shows(driver, wait, text):
    wait.until(lambda d: text in page_text(d))
    return True


def element_with_text(driver, text, tag="*"):
    return driver.find_element(By.XPATH, f"//{tag}[contains(normalize-space(.), {text!r})]")


def click(driver, element):
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", element)
    element.click()


def fill(driver, name, value):
    field = driver.find_element(By.NAME, name)
    field.clear()
    field.send_keys(value)


def accept_confirm(wait):
    wait.until(EC.alert_is_present()).accept()


def accept_if_confirm(driver):
    try:
        driver.switch_to.alert.accept()
    except Exception:
        pass


def take_quiz(driver, wait, letters_by_question):
    """Answer every question on the Take Quiz page (letters in page order) and submit."""
    names = []
    for radio in driver.find_elements(By.CSS_SELECTOR, "input[type=radio][name^='question_']"):
        if radio.get_attribute("name") not in names:
            names.append(radio.get_attribute("name"))
    for name, letter in zip(names, letters_by_question):
        click(driver, driver.find_element(By.CSS_SELECTOR, f"input[name='{name}'][value='{letter}']"))
    click(driver, driver.find_element(By.ID, "quizSubmitButton"))
    return len(names)


def innermost_with(driver, text, css_inside):
    """The smallest element that contains ``text`` and an element matching ``css_inside``."""
    candidates = driver.find_elements(By.XPATH, f"//*[contains(normalize-space(.), {text!r})]")
    best = None
    for el in candidates:
        if el.find_elements(By.CSS_SELECTOR, css_inside):
            best = el
    return best


@pytest.fixture
def fig(driver, request):
    folder = ARTIFACTS / "integration"
    folder.mkdir(parents=True, exist_ok=True)

    def take(number, element=None):
        if element is not None:
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", element)
        else:
            driver.execute_script("window.scrollTo(0, 0);")
        time.sleep(0.4)
        driver.save_screenshot(str(folder / f"{request.node.name}_fig{number}.png"))

    return take


# ---------------------------------------------------------------- cases

def test_integ_01_submit_course_then_admin_sees_pending(driver, wait, fig):
    course = F("courses", "int_submit")
    login(driver, wait, "teacher")
    driver.get(url("/teacher/my-courses"))
    buttons = [b for b in driver.find_elements(By.CSS_SELECTOR, f"form[action$='/teacher/courses/{course}/submit'] button") if b.is_displayed()]
    click(driver, buttons[0])
    assert shows(driver, wait, "submitted for approval successfully")
    fig(1)

    login(driver, wait, "dean")
    driver.get(url("/courses"))
    assert shows(driver, wait, "Cloud Computing Basics")
    approve = driver.find_element(By.CSS_SELECTOR, f"form[action$='/courses/{course}/approve'] button")
    driver.find_element(By.CSS_SELECTOR, f"form[action$='/courses/{course}/reject'] button")
    fig(2, approve)


def test_integ_02_admin_approves_then_marketplace_lists(driver, wait, fig):
    course = F("courses", "c")
    login(driver, wait, "dean")
    driver.get(url("/courses"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/courses/{course}/approve'] button"))
    assert shows(driver, wait, "Course approved and published successfully.")
    fig(1)

    login(driver, wait, "stu_outsider")
    driver.get(url("/marketplace"))
    assert shows(driver, wait, "Database Fundamentals")
    fig(2, driver.find_element(By.XPATH, "//*[normalize-space(text())='Database Fundamentals']"))


def test_integ_03_new_category_available_in_create_course(driver, wait, fig):
    login(driver, wait, "edp")
    driver.get(url("/course-categories/create"))
    fill(driver, "name", "Cybersecurity")
    fill(driver, "description", "Courses about protecting systems and data.")
    click(driver, driver.find_element(By.CSS_SELECTOR, "form.sad-card button[type=submit]"))
    assert shows(driver, wait, "Category created successfully.")
    fig(1, element_with_text(driver, "Cybersecurity", "h3"))

    login(driver, wait, "teacher")
    driver.get(url("/teacher/courses/create"))
    select = Select(driver.find_element(By.NAME, "category_id"))
    assert "Cybersecurity" in [o.text.strip() for o in select.options]
    select.select_by_visible_text("Cybersecurity")
    fill(driver, "title", "Introduction to Cybersecurity")
    fig(2, driver.find_element(By.NAME, "category_id"))


def test_integ_04_preview_then_enroll_and_study(driver, wait, fig):
    login(driver, wait, "stu_outsider")
    driver.get(url(f"/marketplace/{F('courses', 'p')}"))
    assert shows(driver, wait, "Preview Lesson")
    click(driver, driver.find_element(By.CSS_SELECTOR, "[data-preview-lesson]"))
    assert shows(driver, wait, "You are previewing this lesson. Enroll to access the full course.")
    banner = driver.find_element(By.CSS_SELECTOR, "[data-preview-banner]")
    fig(1)

    click(driver, banner.find_element(By.CSS_SELECTOR, "[data-enroll-button]"))
    wait.until(lambda d: "/preview/" not in d.current_url)
    driver.get(url(f"/lesson/{F('lessons', 'p1')}"))
    assert shows(driver, wait, "Mark as Complete")
    assert "You are previewing this lesson." not in page_text(driver)
    fig(2)
    driver.get(url("/my-courses"))
    assert shows(driver, wait, "Networking Fundamentals")


def test_integ_05_lesson_completion_updates_progress(driver, wait, fig):
    login(driver, wait, "stu_prog")
    driver.get(url(f"/lesson/{F('lessons', 'a2')}"))
    click(driver, element_with_text(driver, "Mark as Complete", "button"))
    assert shows(driver, wait, "Lesson marked as completed.")
    fig(1)

    driver.get(url(f"/learn/{F('courses', 'a')}"))
    assert shows(driver, wait, "2 of 4 lessons")
    assert "50% complete" in page_text(driver)
    fig(2, element_with_text(driver, "Course Progress", "p"))


def test_integ_06_ai_generated_quiz_taken_by_student(driver, wait, fig):
    course, lesson = F("courses", "int_ai"), F("lessons", "int_ai_quiz")
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher/lessons/{lesson}/quiz"))
    fill(driver, "title", "Quiz: Data Basics")
    fill(driver, "passing_score", "75")
    Select(driver.find_element(By.NAME, "question_count")).select_by_value("3")
    click(driver, driver.find_element(By.CSS_SELECTOR, "[data-ai-generate-button]"))
    accept_if_confirm(driver)
    assert shows(driver, wait, "Which Python structure stores key-value pairs?")
    click(driver, driver.find_element(By.CSS_SELECTOR, "button[name=intent][value=publish]"))
    accept_if_confirm(driver)
    assert shows(driver, wait, "Quiz published.")
    questions = driver.find_elements(By.XPATH, "//*[self::h2 or self::h3][normalize-space(.)='Questions']")
    fig(1, questions[0] if questions else None)

    driver.get(url("/teacher/my-courses"))
    buttons = [b for b in driver.find_elements(By.CSS_SELECTOR, f"form[action$='/teacher/courses/{course}/submit'] button") if b.is_displayed()]
    click(driver, buttons[0])
    assert shows(driver, wait, "submitted for approval successfully")
    login(driver, wait, "dean")
    driver.get(url("/courses"))
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/courses/{course}/approve'] button"))
    assert shows(driver, wait, "Course approved and published successfully.")

    login(driver, wait, "stu_take")
    driver.get(url(f"/marketplace/{course}"))
    click(driver, driver.find_element(By.CSS_SELECTOR, "[data-enroll-button]"))
    wait.until(lambda d: f"/marketplace/{course}" not in d.current_url or "enrolled" in page_text(d).lower())
    driver.get(url(f"/lesson/{lesson}"))
    assert shows(driver, wait, "Which Python structure stores key-value pairs?")
    assert take_quiz(driver, wait, ["A", "A", "A"]) == 3
    assert shows(driver, wait, "Latest score: 100.0%")
    fig(2, element_with_text(driver, "Latest score: 100.0%", "p"))


def test_integ_07_failing_score_then_retake(driver, wait, fig):
    login(driver, wait, "stu_fail50")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    take_quiz(driver, wait, ["A", "B", "A", "A"])
    assert shows(driver, wait, "Latest score: 50.0%")
    assert "Needs Retake" in page_text(driver)
    fig(1, element_with_text(driver, "Latest score: 50.0%", "p"))

    click(driver, element_with_text(driver, "Retake Quiz", "a"))
    take_quiz(driver, wait, ["A", "B", "C", "D"])
    assert shows(driver, wait, "Latest score: 100.0%")
    assert "Quiz Passed" in page_text(driver)
    fig(2, element_with_text(driver, "Latest score: 100.0%", "p"))


def test_integ_08_quiz_attempt_in_admin_quiz_results(driver, wait, fig):
    login(driver, wait, "stu_full")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    take_quiz(driver, wait, ["A", "B", "C", "D"])
    assert shows(driver, wait, "Latest score: 100.0%")
    fig(1, element_with_text(driver, "Latest score: 100.0%", "p"))

    login(driver, wait, "dean")
    driver.get(url("/quiz-results"))
    assert shows(driver, wait, "Eli Garcia")
    row = driver.find_element(By.XPATH, "//tr[contains(., 'Eli Garcia')]")
    assert "100" in row.text
    fig(2, row)


def test_integ_09_teacher_assignment_submitted_by_student(driver, wait, fig):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher/courses/{F('courses', 'a')}/assignments/create"))
    fill(driver, "title", "Reflection: Control Structures")
    fill(driver, "description", "In three to five sentences, explain when you would use a loop instead of an if statement.")
    fill(driver, "max_score", "100")
    publish = driver.find_element(By.NAME, "is_published")
    if not publish.is_selected():
        click(driver, publish)
    click(driver, driver.find_element(By.CSS_SELECTOR, "form[action$='/assignments'] button[type=submit]"))
    assert shows(driver, wait, "Assignment created successfully.")
    fig(1)

    login(driver, wait, "stu_lesson")
    driver.get(url("/student/assignments"))
    assert shows(driver, wait, "Reflection: Control Structures")
    card = innermost_with(driver, "Reflection: Control Structures", "a[href*='/student/assignments/']")
    click(driver, card.find_element(By.CSS_SELECTOR, "a[href*='/student/assignments/']"))
    fill(driver, "answer_text", "I use a loop when the same steps repeat for every item, and an if statement when I only need to choose one path.")
    click(driver, driver.find_element(By.CSS_SELECTOR, "form[action$='/submit'] button[type=submit]"))
    assert shows(driver, wait, "Assignment submitted successfully.")
    fig(2)


def _submit_activity(driver, wait, activity, answer):
    driver.get(url(f"/student/assignments/{activity}"))
    fill(driver, "answer_text", answer)
    click(driver, driver.find_element(By.CSS_SELECTOR, "form[action$='/submit'] button[type=submit]"))
    assert shows(driver, wait, "submitted successfully.")


def _teacher_grade(driver, wait, activity, student_name, score, feedback):
    driver.get(url(f"/teacher/assignments/{activity}/submissions"))
    assert shows(driver, wait, student_name)
    container = innermost_with(driver, student_name, "a[href*='/teacher/submissions/']")
    click(driver, container.find_element(By.CSS_SELECTOR, "a[href*='/teacher/submissions/']"))
    fill(driver, "score", str(score))
    fill(driver, "feedback", feedback)
    click(driver, driver.find_element(By.CSS_SELECTOR, "button[name=status][value=graded]"))
    assert shows(driver, wait, "Submission graded successfully.")


def test_integ_10_grade_below_75_allows_resubmission(driver, wait, fig):
    activity = F("activities", "p")
    login(driver, wait, "stu_late")
    _submit_activity(driver, wait, activity, "A router, a switch, three computers and a printer share one office network.")

    login(driver, wait, "teacher")
    _teacher_grade(driver, wait, activity, "Yuri Bautista", 60, "Label each device and explain how they connect.")
    fig(1)

    login(driver, wait, "stu_late")
    driver.get(url(f"/student/assignments/{activity}"))
    assert shows(driver, wait, "60")
    assert driver.find_elements(By.NAME, "answer_text"), "the resubmission form should be offered"
    fig(2, driver.find_element(By.NAME, "answer_text"))


def test_integ_11_passing_grade_issues_certificate(driver, wait, fig):
    course, activity = F("courses", "f"), F("activities", "f")
    login(driver, wait, "stu_cert_active")
    driver.get(url(f"/learn/{course}"))
    click(driver, driver.find_element(By.XPATH, "//a[contains(@href, '/lesson/') and (contains(., 'Start') or contains(., 'Continue') or contains(., 'Review'))]"))
    click(driver, element_with_text(driver, "Mark as Complete", "button"))
    assert shows(driver, wait, "Lesson marked as completed.")
    _submit_activity(driver, wait, activity, "My final project proposal and report.")
    driver.get(url(f"/learn/{course}"))
    assert shows(driver, wait, "Waiting for your teacher to grade: Final Project")

    login(driver, wait, "teacher")
    _teacher_grade(driver, wait, activity, "Quin Salazar", 90, "Complete and well organized.")
    fig(1)

    login(driver, wait, "stu_cert_active")
    driver.get(url("/my-certificates"))
    assert shows(driver, wait, "Capstone Project Writing")
    fig(2)


def test_integ_12_quiz_result_drives_ai_recommendation(driver, wait, fig):
    login(driver, wait, "stu_reviewer")
    driver.get(url(f"/quiz/{F('quizzes', 'a')}/take"))
    take_quiz(driver, wait, ["A", "B", "C", "D"])
    assert shows(driver, wait, "Latest score: 100.0%")
    fig(1, element_with_text(driver, "Latest score: 100.0%", "p"))

    driver.get(url("/recommended-courses"))
    assert shows(driver, wait, "Fake Gemini explanation (Selenium test double).")
    fig(2)


def test_integ_13_admin_generated_recommendation_seen_by_student(driver, wait, fig):
    login(driver, wait, "dean")
    driver.get(url("/ai-recommendations/create"))
    Select(driver.find_element(By.NAME, "student_id")).select_by_visible_text("Uma Wong")
    click(driver, driver.find_element(By.CSS_SELECTOR, "form[action$='/ai-recommendations'] button[type=submit]"))
    assert shows(driver, wait, "AI recommendation generated successfully.")
    row = driver.find_element(By.XPATH, "//tr[contains(., 'Uma Wong')]")
    assert "Advanced Algorithms" in row.text
    fig(1, row)

    login(driver, wait, "stu_ai")
    driver.get(url("/recommended-courses"))
    assert shows(driver, wait, "Advanced Algorithms")
    assert "ready for advanced learning materials" in page_text(driver)
    fig(2)


def test_integ_14_removal_request_reviewed_by_department_head(driver, wait, fig):
    login(driver, wait, "teacher")
    driver.get(url(f"/teacher-courses/{F('courses', 'a')}/students"))
    student = F("users", "stu_rm1", "id")
    reason = driver.find_element(By.ID, f"removal_reason_{student}")
    click(driver, reason.find_element(By.XPATH, "./ancestor::details/summary"))
    reason.send_keys("Student has been inactive for the whole term.")
    click(driver, reason.find_element(By.XPATH, "./following-sibling::button"))
    accept_confirm(wait)
    assert shows(driver, wait, "Student removal request submitted successfully.")
    fig(1)

    login(driver, wait, "dean")
    driver.get(url("/admin/student-removal-requests"))
    assert shows(driver, wait, "Rey Tan")
    request_box = innermost_with(driver, "Rey Tan", "button[value=approved]")
    assert "Student has been inactive for the whole term." in request_box.text
    fig(2, request_box)
    click(driver, request_box.find_element(By.CSS_SELECTOR, "button[value=approved]"))
    accept_confirm(wait)
    assert shows(driver, wait, "Removal request approved successfully.")
    login(driver, wait, "stu_rm1")
    driver.get(url(f"/learn/{F('courses', 'a')}"))
    assert shows(driver, wait, "403")


def test_integ_15_hidden_review_removed_from_course_page(driver, wait, fig):
    review = F("reviews", "spam")
    login(driver, wait, "dean")
    driver.get(url("/course-reviews"))
    driver.find_element(By.ID, f"hidden_reason_{review}").send_keys("Contains a spam link.")
    click(driver, driver.find_element(By.CSS_SELECTOR, f"form[action$='/course-reviews/{review}/hide'] button"))
    assert shows(driver, wait, "The review is now hidden.")
    fig(1)

    login(driver, wait, "stu_outsider")
    driver.get(url(f"/marketplace/{F('courses', 'a')}"))
    assert shows(driver, wait, "Good start, but I need more examples.")
    assert "Visit my site for free answers!!!" not in page_text(driver)
    fig(2, driver.find_element(By.ID, "reviews"))
