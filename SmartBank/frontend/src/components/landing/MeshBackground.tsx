export function MeshBackground() {
  return (
    <div className="absolute inset-0 overflow-hidden pointer-events-none [mask-image:radial-gradient(ellipse_at_center,black_50%,transparent_85%)]">
      {/* Light mode orbs — reduced to 2, blur-3xl */}
      <div className="absolute inset-0 dark:hidden">
        <div className="absolute top-0 left-1/4 w-[500px] h-[500px] rounded-full bg-blue-400/20 blur-3xl" />
        <div className="absolute bottom-0 right-1/4 w-[500px] h-[400px] rounded-full bg-indigo-300/15 blur-3xl" />
      </div>
      {/* Dark mode orbs — reduced to 2, blur-3xl */}
      <div className="absolute inset-0 hidden dark:block">
        <div className="absolute top-0 left-1/4 w-[500px] h-[500px] rounded-full bg-blue-600/25 blur-3xl" />
        <div className="absolute bottom-0 right-1/4 w-[500px] h-[400px] rounded-full bg-indigo-700/20 blur-3xl" />
      </div>

      {/* Grid overlay */}
      <div
        className="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]"
        style={{
          backgroundImage:
            "linear-gradient(to right, currentColor 1px, transparent 1px), linear-gradient(to bottom, currentColor 1px, transparent 1px)",
          backgroundSize: "60px 60px",
        }}
      />
    </div>
  );
}
