import { Component, type ErrorInfo, type ReactNode } from "react";
import { Button } from "@/components/ui/button";

type ErrorBoundaryProps = {
  children: ReactNode;
};

type ErrorBoundaryState = {
  error: Error | null;
};

export class ErrorBoundary extends Component<
  ErrorBoundaryProps,
  ErrorBoundaryState
> {
  state: ErrorBoundaryState = { error: null };

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error("Uncaught render error", error, info.componentStack);
  }

  render() {
    if (!this.state.error) {
      return this.props.children;
    }

    return (
      <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-background p-6 text-center">
        <h1 className="text-xl font-semibold tracking-tight">
          Something went wrong
        </h1>
        <p className="max-w-md text-sm text-muted-foreground">
          {this.state.error.message || "An unexpected error occurred."}
        </p>
        <div className="flex gap-2">
          <Button onClick={() => window.location.reload()}>Reload page</Button>
          <Button variant="outline" asChild>
            <a href="/login">Back to sign in</a>
          </Button>
        </div>
      </div>
    );
  }
}
